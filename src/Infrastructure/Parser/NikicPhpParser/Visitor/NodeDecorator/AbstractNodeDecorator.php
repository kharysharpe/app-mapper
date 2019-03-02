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
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\AstNodeNotFoundException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\CircularReferenceDetectedException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\ParentNodeNotFoundException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeCollection;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeDecoratorAccessorTrait;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Type;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeCollection;
use Hgraca\PhpExtension\Reflection\ReflectionHelper;
use Hgraca\PhpExtension\String\ClassHelper;
use Hgraca\PhpExtension\String\StringHelper;
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
    protected $parentNode;

    /**
     * @var TypeCollection
     */
    protected $typeCollection;

    /**
     * @var NodeCollection
     */
    protected $nodeCollection;

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
            try {
                $resolvedTypeCollection = $this->resolveTypeCollection();
                if ($resolvedTypeCollection->isEmpty()) {
                    $resolvedTypeCollection = $resolvedTypeCollection->addType(Type::constructUnknownFromNode($this));
                }
            } catch (CircularReferenceDetectedException $e) {
                StaticLoggerFacade::notice(
                    "Caught CircularReferenceDetectedException when resolving a type collection.\n"
                    . 'Ignoring, as it is probably an assignment from an expression that uses the assignee'
                );
                $resolvedTypeCollection = new TypeCollection();
            }
            $this->typeCollection = $this->typeCollection->addTypeCollection($resolvedTypeCollection);
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
            $typeCollection = $typeCollection->addTypeCollection($siblingNodeDecorator->getTypeCollection());
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
            return $this->getFirstParentNodeOfType(Trait_::class);
        }
    }

    public function getEnclosingMethodNode(): ClassMethodNodeDecorator
    {
        return $this->getFirstParentNodeOfType(ClassMethod::class);
    }

    protected function getSelfTypeCollection(): TypeCollection
    {
        return $this->getEnclosingInterfaceLikeNode()->getTypeCollection();
    }

    protected function getEnclosingInterfaceLikeNode(): AbstractInterfaceLikeNodeDecorator
    {
        try {
            return $this->getEnclosingClassLikeNode();
        } catch (ParentNodeNotFoundException $e) {
            return $this->getFirstParentNodeOfType(Interface_::class);
        }
    }

    protected function getEnclosingNamespaceNode(): NamespaceNodeDecorator
    {
        return $this->getFirstParentNodeOfType(Namespace_::class);
    }

    protected function getFirstParentNodeOfType(string $type): self
    {
        $nodeDecorator = $this;
        do {
            $nodeDecorator = $nodeDecorator->getParentNode();
        } while (
            $nodeDecorator !== null
            && !$nodeDecorator->isInternalNodeInstanceOf($type)
            && !$nodeDecorator instanceof $type
        );

        if (!$nodeDecorator) {
            throw new ParentNodeNotFoundException($type, $this);
        }

        return $nodeDecorator;
    }

    protected function getTypeCollectionFromUses(string $type): TypeCollection
    {
        $positionOfBrackets = mb_strpos($type, '[');
        $arrayList = $positionOfBrackets ? mb_substr($type, $positionOfBrackets) : '';
        $nestedType = rtrim($type, '[]');
        $namespaceNodeDecorator = $this->getEnclosingNamespaceNode();

        foreach ($namespaceNodeDecorator->getUses() as $useDecorator) {
            $useTypeCollection = $useDecorator->getTypeCollection();
            $useType = $useTypeCollection->getUniqueType()->getFqn();

            if (
                $nestedType === $useType
                || $nestedType === $useDecorator->getAlias()
                || StringHelper::hasEnding($nestedType, $useType)
            ) {
                if ($arrayList) {
                    return new TypeCollection($this->buildTypeFromString($useType . $arrayList));
                }

                return $useTypeCollection;
            }
        }

        return new TypeCollection();
    }

    protected function buildTypeFromString(string $string): Type
    {
        if ($string === 'self' || $string === 'this') {
            return $this->getSelfTypeCollection()->getUniqueType();
        }

        if (StringHelper::hasEnding('[]', $string)) {
            return new Type('array', null, $this->buildTypeFromString(StringHelper::removeFromEnd('[]', $string)));
        }

        try {
            return new Type($string, $this->getNodeDecorator($this->nodeCollection->getAstNode($string)));
        } catch (AstNodeNotFoundException $e) {
            return new Type($string);
        }
    }

    protected function assertNotInCircularReference(): void
    {
        $backtrace = debug_backtrace();
        array_shift($backtrace); // Remove current method from the stack
        $methodName = $backtrace[0]['function']; // Get the method name we want to check for circular reference
        array_shift($backtrace); // Remove from the stack, the method call we want to check for references
        foreach ($backtrace as $key => $methodCall) {
            $circularBacktrace[] = $methodCall;
            if (
                array_key_exists('object', $methodCall)
                && array_key_exists('function', $methodCall)
                && $this === $methodCall['object']
                && $methodName === $methodCall['function']
            ) {
                throw new CircularReferenceDetectedException($this, $circularBacktrace);
            }
        }
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
            $innerNode = ReflectionHelper::getProtectedProperty($loopNode, 'node');
            $nodeTree[] = ClassHelper::extractCanonicalClassName(get_class($loopNode))
                . ' => '
                . ClassHelper::extractCanonicalClassName($innerNode ? get_class($innerNode) : '')
                . ' => '
                . ($loopNode instanceof NamedNodeDecoratorInterface ? $loopNode->getName() : 'no_name');
        } while ($loopNode = $loopNode->getParentNode());

        return $nodeTree;
    }
}
