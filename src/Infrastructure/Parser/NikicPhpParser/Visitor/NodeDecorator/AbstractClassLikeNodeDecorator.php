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
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeNodeCollector;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\TraitUse;
use function array_key_exists;

/**
 * @property Class_|Trait_ $node
 */
abstract class AbstractClassLikeNodeDecorator extends AbstractInterfaceLikeNodeDecorator implements NamedNodeDecoratorInterface
{
    /**
     * @var TypeNodeCollector
     */
    private $propertyNodesSiblingCollection;

    /**
     * @var TypeCollection[]
     */
    private $propertyNodesTypeCollections = [];

    /**
     * @var PropertyNodeDecorator[]
     */
    private $propertyList = [];

    public function __construct(Node $node, AbstractNodeDecorator $parentNode = null)
    {
        parent::__construct($node, $parentNode);
        $this->propertyNodesSiblingCollection = new TypeNodeCollector();
    }

    abstract protected function getPropertyTypeCollectionFromHierarchy(
        NamedNodeDecoratorInterface $nodeDecorator
    ): TypeCollection;

    public function getParentName(): ?NameNodeDecorator
    {
        return $this->node->extends
            ? $this->getNodeDecorator($this->node->extends)
            : null;
    }

    /**
     * @return NameNodeDecorator[]
     */
    public function getParentNameList(): array
    {
        $parentName = $this->getParentName();

        return $parentName ? [$parentName] : [];
    }

    public function getPropertyTypeCollection(NamedNodeDecoratorInterface $nodeDecorator): TypeCollection
    {
        if (!$this->hasDeclaredProperty($nodeDecorator)) {
            return $this->getPropertyTypeCollectionFromHierarchy($nodeDecorator);
        }

        $propertyName = $nodeDecorator->getName();
        if (!$this->isPropertyTypeCollectionResolved($nodeDecorator)) {
            $this->resolvePropertyTypeCollection($nodeDecorator);
        }

        return $this->propertyNodesTypeCollections[$propertyName];
    }

    /**
     * @return PropertyNodeDecorator[]
     */
    public function getDeclaredProperties(): array
    {
        if (empty($this->propertyList)) {
            foreach ($this->node->stmts as $stmt) {
                if ($stmt instanceof Property) {
                    /** @var PropertyNodeDecorator $propertyDecorator */
                    $propertyDecorator = $this->getNodeDecorator($stmt);
                    $this->propertyList[$propertyDecorator->getName()] = $propertyDecorator;
                }
            }
        }

        return $this->propertyList;
    }

    protected function getPropertyTypeCollectionFromTraits(NamedNodeDecoratorInterface $nodeDecorator): TypeCollection
    {
        /** @var TraitNodeDecorator[] $traitList */
        $traitList = $this->getTraitList();

        $typeCollection = new TypeCollection();
        foreach ($traitList as $trait) {
            $typeCollection = $typeCollection->addTypeCollection($trait->getPropertyTypeCollection($nodeDecorator));
        }

        return $typeCollection;
    }

    /**
     * @return TraitNodeDecorator[]
     */
    protected function getTraitList(): array
    {
        $nestedTraitList = [];

        foreach ($this->node->stmts as $stmt) {
            if ($stmt instanceof TraitUse) {
                /** @var TraitUseNodeDecorator $traitUseNodeDecorator */
                $traitUseNodeDecorator = $this->getNodeDecorator($stmt);
                $nestedTraitList[] = $traitUseNodeDecorator->getTraitNodeDecoratorList();
            }
        }

        return !empty($nestedTraitList)
            ? array_merge(...$nestedTraitList)
            : [];
    }

    private function isPropertyTypeCollectionResolved(NamedNodeDecoratorInterface $nodeDecorator): bool
    {
        return array_key_exists($nodeDecorator->getName(), $this->propertyNodesTypeCollections);
    }

    private function hasDeclaredProperty(NamedNodeDecoratorInterface $nodeDecorator): bool
    {
        return array_key_exists($nodeDecorator->getName(), $this->getDeclaredProperties());
    }

    private function getDeclaredProperty(NamedNodeDecoratorInterface $nodeDecorator): PropertyNodeDecorator
    {
        $declaredProperties = $this->getDeclaredProperties();

        return $declaredProperties[$nodeDecorator->getName()];
    }

    public function storePropertiesSiblings(TypeNodeCollector $nodeCollector): void
    {
        $this->propertyNodesSiblingCollection = $nodeCollector;
    }

    private function resolvePropertyTypeCollection(NamedNodeDecoratorInterface $nodeDecorator): void
    {
        $typeCollection = $this->getDeclaredProperty($nodeDecorator)->getTypeCollection();
        foreach ($this->propertyNodesSiblingCollection->getNodesFor($nodeDecorator) as $siblingNode) {
            $typeCollection = $typeCollection->addTypeCollection($siblingNode->getTypeCollection());
        }
        $this->propertyNodesTypeCollections[$nodeDecorator->getName()] = $typeCollection;
    }
}
