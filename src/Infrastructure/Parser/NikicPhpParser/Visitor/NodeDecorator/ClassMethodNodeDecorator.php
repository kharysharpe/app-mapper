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

    public function isPublic(): bool
    {
        return $this->node->isPublic();
    }
}
