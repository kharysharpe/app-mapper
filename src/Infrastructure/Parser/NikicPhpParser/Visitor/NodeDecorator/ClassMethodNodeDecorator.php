<?php

declare(strict_types=1);

/*
 * This file is part of the Application mapper application,
 * following the Explicit Architecture principles.
 *
 * @link https://herbertograca.com/2017/11/16/explicit-architecture-01-ddd-hexagonal-onion-clean-cqrs-how-i-put-it-all-together
 * @link https://herbertograca.com/2018/07/07/more-than-concentric-layers/
 *
 * (c) Herberto Graça
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator;

use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\ParentNodeNotFoundException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\UnknownParameterException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeCollection;
use PhpParser\Node\Stmt\ClassMethod;

/**
 * @property ClassMethod $node
 */
final class ClassMethodNodeDecorator extends AbstractNodeDecorator implements NamedNodeDecoratorInterface
{
    public function __construct(ClassMethod $node, AbstractNodeDecorator $parentNode)
    {
        parent::__construct($node, $parentNode);
    }

    public function resolveTypeCollection(): TypeCollection
    {
        return $this->node->returnType
            ? $this->getNodeDecorator($this->node->returnType)->getTypeCollection()
            : new TypeCollection();
    }

    public function getName(): string
    {
        return (string) $this->node->name;
    }

    public function getReturnTypeCollection(): TypeCollection
    {
        return $this->getTypeCollection();
    }

    public function getParameter(int $index): ParamNodeDecorator
    {
        return $this->getNodeDecorator($this->node->params[$index]);
    }

    public function getParameterIndex(ParamNodeDecorator $searchParamDecorator): int
    {
        foreach ($this->node->params as $index => $param) {
            $currentParamDecorator = $this->getNodeDecorator($param);

            if ($currentParamDecorator === $searchParamDecorator) {
                return $index;
            }
        }

        throw new UnknownParameterException($searchParamDecorator);
    }

    public function isPublic(): bool
    {
        return $this->node->isPublic();
    }

    public function isWithinClass(): bool
    {
        try {
            if ($this->getEnclosingClassLikeNode() instanceof StmtClassNodeDecorator) {
                return true;
            }
        } catch (ParentNodeNotFoundException $e) {
        }

        return false;
    }

    public function isWithinTrait(): bool
    {
        try {
            if ($this->getEnclosingClassLikeNode() instanceof TraitNodeDecorator) {
                return true;
            }
        } catch (ParentNodeNotFoundException $e) {
        }

        return false;
    }
}
