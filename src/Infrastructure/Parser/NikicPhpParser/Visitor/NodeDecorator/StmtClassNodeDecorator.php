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
use PhpParser\Node\Stmt\Class_;

/**
 * @property Class_ $node
 */
final class StmtClassNodeDecorator extends AbstractClassLikeNodeDecorator
{
    public function __construct(Class_ $node, AbstractNodeDecorator $parentNode)
    {
        parent::__construct($node, $parentNode);
    }

    public function getName(): string
    {
        return (string) $this->node->name;
    }

    /**
     * @return NameNodeDecorator[]
     */
    public function getInterfaces(): array
    {
        $interfaceList = [];

        foreach ($this->node->implements as $interface) {
            $interfaceList[] = $this->getNodeDecorator($interface);
        }

        return $interfaceList;
    }

    /**
     * @return static|null
     */
    public function getParent(): ?self
    {
        $parentName = $this->getParentName();

        return $parentName
            ? $parentName->getTypeCollection()->getUniqueType()->getNodeDecorator()
            : null;
    }

    public function isAbstract(): bool
    {
        return $this->node->isAbstract();
    }

    protected function getPropertyTypeCollectionFromHierarchy(
        NamedNodeDecoratorInterface $nodeDecorator
    ): TypeCollection {
        return $this->getPropertyTypeCollectionFromParent($nodeDecorator)
            ->addTypeCollection($this->getPropertyTypeCollectionFromTraits($nodeDecorator));
    }

    private function getPropertyTypeCollectionFromParent(NamedNodeDecoratorInterface $nodeDecorator): TypeCollection
    {
        $parent = $this->getParent();

        return $parent
            ? $parent->getPropertyTypeCollection($nodeDecorator)
            : new TypeCollection();
    }
}
