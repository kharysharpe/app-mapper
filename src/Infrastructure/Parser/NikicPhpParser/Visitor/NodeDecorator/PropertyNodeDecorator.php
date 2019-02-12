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

use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeCollection;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeDecoratorAccessorTrait;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Type;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeCollection;
use Hgraca\PhpExtension\String\StringHelper;
use Hgraca\PhpExtension\Type\TypeHelper;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Property;

/**
 * @property Property $node
 */
final class PropertyNodeDecorator extends AbstractNodeDecorator implements NamedNodeDecoratorInterface
{
    use NodeDecoratorAccessorTrait;

    public function __construct(Property $node, AbstractNodeDecorator $parentNode, NodeCollection $nodeCollection)
    {
        parent::__construct($node, $parentNode);
        $this->nodeCollection = $nodeCollection;
    }

    public function resolveTypeCollection(): TypeCollection
    {
        return $this->getTypeCollectionFromDocBlock();
    }

    public function getName(): string
    {
        return (string) $this->node->props[0]->name;
    }

    private function getTypeCollectionFromDocBlock(): TypeCollection
    {
        $typeCollection = new TypeCollection();

        foreach ($this->node->getAttribute('comments') ?? [] as $comment) {
            foreach (StringHelper::extractFromBetween('@var ', "\n", $comment->getText()) as $typeList) {
                foreach (explode('|', $typeList) as $type) {
                    if (TypeHelper::isNativeType($type)) {
                        $typeCollection = $typeCollection->addType(new Type($type));
                        continue;
                    }

                    $typeCollectionFromUses = $this->getTypeCollectionFromUses($type);

                    if (!$typeCollectionFromUses->isEmpty()) {
                        $typeCollection = $typeCollection->addTypeCollection($typeCollectionFromUses);
                        continue;
                    }

                    $typeCollection = $typeCollection->addType(
                        $this->assumeIsInSameNamespace($type)
                    );
                }
            }
        }

        return $typeCollection;
    }

    private function assumeIsInSameNamespace(string $type): Type
    {
        /** @var NamespaceNodeDecorator $namespaceNodeDecorator */
        $namespaceNodeDecorator = $this->getFirstParentNodeOfType(Namespace_::class);
        $namespacedType = $namespaceNodeDecorator->getName() . "\\$type";

        return $this->buildTypeFromString($namespacedType);
    }
}
