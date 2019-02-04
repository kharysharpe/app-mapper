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

use Hgraca\AppMapper\Core\Port\Logger\StaticLoggerFacade;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\CircularReferenceDetectedException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\ParentNodeNotFoundException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeDecoratorAccessorTrait;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Type;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeCollection;
use Hgraca\PhpExtension\Reflection\ReflectionHelper;
use Hgraca\PhpExtension\String\ClassHelper;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Trait_;

abstract class AbstractNodeDecorator
{
    use NodeDecoratorAccessorTrait;

    /**
     * @var Node
     */
    protected $node;

    /**
     * @var self|null
     */
    private $parentNode;

    /**
     * @var TypeCollection
     */
    private $typeCollection;

    /**
     * @var bool
     */
    private $typeCollectionIsResolved = false;

    /**
     * @var self[]
     */
    private $siblingNodeCollection = [];

    public function __construct(Node $node, self $parentNode = null)
    {
        $this->node = $node;
        $this->parentNode = $parentNode;
        $this->typeCollection = new TypeCollection();
    }

    abstract protected function resolveTypeCollection(): TypeCollection;

    public function addTypeToNode(Type ...$typeList): void
    {
        foreach ($typeList as $type) {
            $this->typeCollection = $this->typeCollection->addType($type);
        }
    }

    public function removeType(Type ...$typeList): void
    {
        foreach ($typeList as $type) {
            $this->typeCollection = $this->typeCollection->removeTypeEqualTo($type);
        }
    }

    /**
     * @return TypeCollection|Type[]
     */
    public function getTypeCollection(): TypeCollection
    {
        if (!$this->typeCollectionIsResolved) {
            $this->typeCollection = $this->typeCollection->addTypeCollection($this->resolveTypeCollection());
            if ($this->typeCollection->isEmpty()) {
                $this->typeCollection = $this->typeCollection->addType(Type::constructUnknownFromNode($this));
            }
            $this->typeCollectionIsResolved = true;
        }

        return $this->typeCollection;
    }

    public function addSiblingNodes(self ...$decoratedNodeList): void
    {
        foreach ($decoratedNodeList as $decoratedNode) {
            $this->siblingNodeCollection[] = $decoratedNode;
        }
    }

    protected function getSiblingTypeCollection(): TypeCollection
    {
        $typeCollection = new TypeCollection();

        foreach ($this->siblingNodeCollection as $siblingNodeDecorator) {
            try {
                $typeCollection = $typeCollection->addTypeCollection($siblingNodeDecorator->getTypeCollection());
            } catch (CircularReferenceDetectedException $e) {
                StaticLoggerFacade::notice(
                    "Caught CircularReferenceDetectedException when resolving a sibling type collection.\n"
                    . "Ignoring, as it means a sibling depends on another sibling to figure out its type.\n"
                    . 'It is probably an assignment from an expression that uses the assignee'
                );
            }
        }

        return $typeCollection;
    }

    public function getParentNode(): ?self
    {
        return $this->parentNode;
    }

    public function resolveNodeTreeAsJson(): string
    {
        return json_encode($this->resolveNodeTree(), JSON_PRETTY_PRINT);
    }

    public function getEnclosingClassLikeNode(): AbstractClassLikeNodeDecorator
    {
        try {
            return $this->getFirstParentNodeOfType(Class_::class);
        } catch (ParentNodeNotFoundException $e) {
            try {
                return $this->getFirstParentNodeOfType(Interface_::class);
            } catch (ParentNodeNotFoundException $e) {
                return $this->getFirstParentNodeOfType(Trait_::class);
            }
        }
    }

    public function getEnclosingMethodNode(): ClassMethodNodeDecorator
    {
        return $this->getFirstParentNodeOfType(ClassMethod::class);
    }

    protected function getSelfTypeCollection(): TypeCollection
    {
        return $this->getEnclosingClassLikeNode()->getTypeCollection();
    }

    protected function getEnclosingNamespaceNode(): NamespaceNodeDecorator
    {
        return $this->getFirstParentNodeOfType(Namespace_::class);
    }

    protected function getFirstParentNodeOfType(string $type): self
    {
        $node = $this;
        do {
            $node = $node->getParentNode();
        } while ($node !== null && !$node->isInternalNodeInstanceOf($type));

        if (!$node) {
            throw new ParentNodeNotFoundException($type, $this);
        }

        return $node;
    }

    private function isInternalNodeInstanceOf(string $type): bool
    {
        return $this->node instanceof $type;
    }

    private function resolveNodeTree(): array
    {
        $nodeTree = [];
        $loopNode = $this;
        do {
            $nodeTree[] = ClassHelper::extractCanonicalClassName(get_class($loopNode)) . ' => '
                . ClassHelper::extractCanonicalClassName(
                    get_class(ReflectionHelper::getProtectedProperty($loopNode, 'node'))
                ) . ' => '
                . ($loopNode instanceof NamedNodeDecoratorInterface ? $loopNode->getName() : 'no_name');
        } while ($loopNode = $loopNode->getParentNode());

        return $nodeTree;
    }
}
