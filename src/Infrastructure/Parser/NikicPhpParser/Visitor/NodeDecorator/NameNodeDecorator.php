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

use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\AstNodeNotFoundException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeCollection;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeDecoratorAccessorTrait;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Type;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeCollection;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;

/**
 * @property Name $node
 */
final class NameNodeDecorator extends AbstractNodeDecorator implements NamedNodeDecoratorInterface
{
    use NodeDecoratorAccessorTrait;

    public function __construct(Name $node, AbstractNodeDecorator $parentNode, NodeCollection $nodeCollection)
    {
        parent::__construct($node, $parentNode);
        $this->nodeCollection = $nodeCollection;
    }

    public function resolveTypeCollection(): TypeCollection
    {
        $fqcn = $this->buildFqcn($this->node);

        if ($fqcn === 'self' || $fqcn === 'this') {
            return $this->getSelfTypeCollection();
        }

        try {
            return new TypeCollection(
                new Type($fqcn, $this->getNodeDecorator($this->nodeCollection->getAstNode($fqcn)))
            );
        } catch (AstNodeNotFoundException $e) {
            return new TypeCollection(new Type($fqcn));
        }
    }

    public function getName(): string
    {
        return (string) $this->node;
    }

    private function buildFqcn(Name $name): string
    {
        if ($name->hasAttribute('resolvedName')) {
            /** @var FullyQualified $fullyQualified */
            $fullyQualified = $name->getAttribute('resolvedName');

            return $fullyQualified->toCodeString();
        }

        return implode('\\', $name->parts);
    }
}
