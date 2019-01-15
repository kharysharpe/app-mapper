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

namespace Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor;

use Hgraca\AppMapper\Core\Port\Logger\StaticLoggerFacade;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\MethodNotFoundInClassException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\NotImplementedException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\UnknownVariableException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\UnresolvableNodeTypeException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeCollection;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeTypeManagerTrait;
use Hgraca\PhpExtension\String\ClassHelper;
use Hgraca\PhpExtension\String\StringHelper;
use Hgraca\PhpExtension\Type\TypeHelper;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\BinaryOp\Coalesce;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Ternary;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Foreach_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Use_;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\NodeVisitorAbstract;
use ReflectionClass;
use function get_class;

final class TypeResolverInjectorVisitor extends NodeVisitorAbstract
{
    use NodeTypeManagerTrait;

    /**
     * @var TypeFactory
     */
    private $typeFactory;

    private $propertyCollector;

    private $variableCollector;

    public function __construct(NodeCollection $astCollection)
    {
        $this->typeFactory = new TypeFactory($astCollection);
        $this->propertyCollector = new TypeResolverCollector();
        $this->variableCollector = new TypeResolverCollector();
    }

    /**
     * @uses addStaticCallTypeResolver
     * @uses addCoalesceTypeResolver
     * @uses addTernaryTypeResolver
     * @uses addNullableTypeTypeResolver
     * @uses addAssignTypeResolver
     * @uses addMethodCallTypeResolver
     * @uses addVariableTypeResolver
     * @uses addPropertyFetchTypeResolver
     * @uses addPropertyTypeResolver
     * @uses addUseUseTypeResolver
     */
    public function enterNode(Node $node): void
    {
        if ($this->hasResolverAdderFor($node)) {
            $addResolver = $this->getResolverAdderName($node);
            $this->$addResolver($node);

            return;
        }

        $this->addDefaultTypeResolver($node);
    }

    public function leaveNode(Node $node): void
    {
        switch (true) {
            case $node instanceof Assign:
                $this->assignExpressionTypeResolverToVar($node);
                break;
            case $node instanceof ClassMethod:
                $this->variableCollector->resetCollectedResolvers();
                break;
            case $node instanceof Class_:
                $this->addCollectedPropertyResolversToTheirDeclaration($node);
                $this->propertyCollector->resetCollectedResolvers();
                break;
        }
    }

    private function addDefaultTypeResolver(Node $node): void
    {
        if (!$this->typeFactory->canBuildTypeFor($node)) {
            return;
        }

        self::addTypeResolver(
            $node,
            function () use ($node): TypeCollection {
                return $this->typeFactory->buildTypeCollection($node);
            }
        );
    }

    private function addStaticCallTypeResolver(StaticCall $staticCallNode): void
    {
        self::addTypeResolver(
            $staticCallNode,
            function () use ($staticCallNode): TypeCollection {
                $classTypeCollection = self::getTypeCollectionFromNode($staticCallNode->class);

                $staticCallTypeCollection = new TypeCollection();

                /** @var Type $classType */
                foreach ($classTypeCollection as $classType) {
                    if (!$classType->hasAst()) {
                        continue;
                    }

                    if (!$classType->hasAstMethod((string) $staticCallNode->name)) {
                        continue;
                    }

                    $returnTypeNode = $classType->getAstMethod((string) $staticCallNode->name)->returnType;
                    $staticCallTypeCollection = $staticCallTypeCollection->addTypeCollection(
                        self::getTypeCollectionFromNode($returnTypeNode)
                    );
                }

                return $staticCallTypeCollection;
            }
        );
    }

    private function addCoalesceTypeResolver(Coalesce $coalesceNode): void
    {
        self::addTypeResolver(
            $coalesceNode,
            function () use ($coalesceNode): TypeCollection {
                return self::getTypeCollectionFromNode($coalesceNode->left)
                    ->removeTypeEqualTo($this->typeFactory->buildNull())
                    ->addTypeCollection(self::getTypeCollectionFromNode($coalesceNode->right));
            }
        );
    }

    private function addTernaryTypeResolver(Ternary $ternaryNode): void
    {
        self::addTypeResolver(
            $ternaryNode,
            function () use ($ternaryNode): TypeCollection {
                $typeCollection = $ternaryNode->if === null
                    ? self::getTypeCollectionFromNode($ternaryNode->cond)
                    : self::getTypeCollectionFromNode($ternaryNode->if);

                return $typeCollection->addTypeCollection(self::getTypeCollectionFromNode($ternaryNode->else));
            }
        );
    }

    private function addNullableTypeTypeResolver(NullableType $nullableTypeNode): void
    {
        self::addTypeResolver(
            $nullableTypeNode,
            function () use ($nullableTypeNode): TypeCollection {
                return self::getTypeCollectionFromNode($nullableTypeNode->type)
                    ->addType($this->typeFactory->buildNull());
            }
        );
    }

    private function assignExpressionTypeResolverToVar(Assign $assignNode): void
    {
        $variable = $assignNode->var;
        $expression = $assignNode->expr;

        $resolver = function () use ($expression): TypeCollection {
            try {
                return self::resolveType($expression);
            } catch (UnresolvableNodeTypeException $e) {
                StaticLoggerFacade::warning(
                    "Silently ignoring a UnresolvableNodeTypeException in this filter.\n"
                    . 'This is failing, at least, for nested method calls like'
                    . '`$invoice->transactions->first()->getServicePro();`.' . "\n"
                    . "This should be fixed in the type addition visitors.\n"
                    . $e->getMessage(),
                    [__METHOD__]
                );

                return new TypeCollection();
            }
        };

        self::addTypeResolver($variable, $resolver);
        if ($variable instanceof Variable) {
            // Assignment to variable
            $this->variableCollector->resetResolverCollection($this->getVariableName($variable), $resolver);
        } elseif ($variable instanceof PropertyFetch) {
            // Assignment to property
            $this->propertyCollector->collectResolver($this->getPropertyName($variable), $resolver);
        }
    }

    private function addVariableTypeResolver(Variable $variableNode): void
    {
        if ($variableNode->name === 'this') {
            $this->addDefaultTypeResolver($variableNode);

            return;
        }

        $parentNode = $variableNode->getAttribute('parentNode');

        if ($parentNode instanceof Assign && $variableNode === $parentNode->var) {
            return;
        }

        if ($parentNode instanceof Param) {
            $resolver = function () use ($parentNode): TypeCollection {
                return self::resolveType($parentNode);
            };

            self::addTypeResolver($variableNode, $resolver);
            $this->collectTypeResolver($variableNode, $resolver);

            return;
        }

        $this->addCollectedVariableResolver($variableNode);
    }

    private function addPropertyFetchTypeResolver(PropertyFetch $propertyFetchNode): void
    {
        $parentNode = $propertyFetchNode->getAttribute('parentNode');

        if ($parentNode instanceof Assign && $propertyFetchNode === $parentNode->var) {
            return;
        }

        $this->addCollectedPropertyFetchResolver($propertyFetchNode);
    }

    private function addMethodCallTypeResolver(MethodCall $methodCall): void
    {
        self::addTypeResolver(
            $methodCall,
            function () use ($methodCall): TypeCollection {
                $varCollectionType = self::getTypeCollectionFromNode($methodCall->var);

                $typeCollection = new TypeCollection();

                /** @var Type $methodCallVarType */
                foreach ($varCollectionType as $methodCallVarType) {
                    if (!$methodCallVarType->hasAst()) {
                        continue;
                    }

                    try {
                        $returnTypeNode = $methodCallVarType->getAstMethod((string) $methodCall->name)->returnType;
                        $typeCollection = $typeCollection->addTypeCollection(
                            self::getTypeCollectionFromNode($returnTypeNode)
                        );
                    } catch (UnresolvableNodeTypeException $e) {
                        StaticLoggerFacade::warning(
                            "Silently ignoring a UnresolvableNodeTypeException.\n"
                            . 'This is failing, at least, for nested method calls like'
                            . '`$invoice->transactions->first()->getServicePro();`.' . "\n"
                            . "This should be fixed in the type addition visitors, otherwise we might be missing events.\n"
                            . $e->getMessage(),
                            [__METHOD__]
                        );
                    } catch (MethodNotFoundInClassException $e) {
                        StaticLoggerFacade::warning(
                            "Silently ignoring a MethodNotFoundInClassException.\n"
                            . "This should be fixed, otherwise we might be missing events.\n"
                            . $e->getMessage(),
                            [__METHOD__]
                        );
                    }
                }

                return $typeCollection;
            }
        );
    }

    private function addPropertyTypeResolver(Property $property): void
    {
        $resolver = function () use ($property): TypeCollection {
            $typeCollection = new TypeCollection();

            foreach ($property->getAttribute('comments') ?? [] as $comment) {
                foreach (StringHelper::extractFromBetween('@var ', "\n", $comment->getText()) as $typeList) {
                    foreach (explode('|', $typeList) as $type) {
                        if (TypeHelper::isNativeType($type)) {
                            $typeCollection = $typeCollection->addType(new Type($type));
                            continue;
                        }

                        $typeCollectionFromUses = $this->getTypeCollectionFromUses($property, $type);

                        if (!$typeCollectionFromUses->isEmpty()) {
                            $typeCollection = $typeCollection->addTypeCollection($typeCollectionFromUses);
                            continue;
                        }

                        $typeCollection = $typeCollection->addType(
                            $this->assumeIsInSameNamespace($property, $type)
                        );
                    }
                }
            }

            return $typeCollection;
        };

        self::addTypeResolver($property, $resolver);
        $this->collectTypeResolver($property, $resolver);
    }

    private function addUseUseTypeResolver(UseUse $useUse): void
    {
        self::addTypeResolver(
            $useUse,
            function () use ($useUse): TypeCollection {
                return self::getTypeCollectionFromNode($useUse->name);
            }
        );
    }

    private function collectTypeResolver(Node $var, callable $resolver): void
    {
        switch (true) {
            case $var instanceof Variable: // Assignment to variable
                $this->variableCollector->collectResolver($this->getVariableName($var), $resolver);
                break;
            case $var instanceof Property: // Declaration of property
            case $var instanceof PropertyFetch: // Assignment to property
                $this->propertyCollector->collectResolver($this->getPropertyName($var), $resolver);
                break;
        }
    }

    private function addCollectedVariableResolver(Variable $variableNode): void
    {
        try {
            self::addTypeResolverCollection(
                $variableNode,
                $this->variableCollector->getCollectedResolverCollection($this->getVariableName($variableNode))
            );
        } catch (UnknownVariableException $e) {
            StaticLoggerFacade::warning(
                "Silently ignoring a UnknownVariableException.\n"
                . "The variable is not in the collector, so we can't add it to the PropertyFetch.\n"
                . $e->getMessage(),
                [__METHOD__]
            );
        }
    }

    /**
     * TODO We are only adding properties types in the class itself.
     *      We should fix this by adding them also to the super classes.
     */
    private function addCollectedPropertyFetchResolver(PropertyFetch $propertyFetch): void
    {
        try {
            self::addTypeResolverCollection(
                $propertyFetch,
                $this->propertyCollector->getCollectedResolverCollection($this->getPropertyName($propertyFetch))
            );
        } catch (UnknownVariableException $e) {
            StaticLoggerFacade::warning(
                "Silently ignoring a UnknownVariableException.\n"
                . "The property is not in the collector, so we can't add it to the PropertyFetch.\n"
                . $e->getMessage(),
                [__METHOD__]
            );
        }
    }

    /**
     * After collecting app possible class properties, we inject them in their declaration
     *
     * TODO We are only adding properties types in the class itself.
     *      We should fix this by adding them also to the super classes.
     */
    private function addCollectedPropertyResolversToTheirDeclaration(Class_ $node): void
    {
        foreach ($node->stmts as $property) {
            if ($this->isCollectedProperty($property)) {
                try {
                    self::addTypeResolverCollection(
                        $property,
                        $this->propertyCollector->getCollectedResolverCollection($this->getPropertyName($property))
                    );
                } catch (UnknownVariableException $e) {
                    StaticLoggerFacade::warning(
                        "Silently ignoring a UnknownVariableException.\n"
                        . "The property is not in the collector, so we can't add it to the Property declaration.\n"
                        . $e->getMessage(),
                        [__METHOD__]
                    );
                }
            }
        }
    }

    private function hasResolverAdderFor(Node $node): bool
    {
        $class = new ReflectionClass($this);

        return $class->hasMethod($this->getResolverAdderName($node));
    }

    private function getResolverAdderName(Node $node): string
    {
        return 'add'
            . ClassHelper::extractCanonicalClassName(get_class($node))
            . 'TypeResolver';
    }

    private function getVariableName(Variable $variable): string
    {
        return $variable->name;
    }

    private function getPropertyName($property): string
    {
        switch (true) {
            case $property instanceof Property:
                return (string) $property->props[0]->name;
                break;
            case $property instanceof PropertyFetch:
                return (string) $property->name;
                break;
            default:
                throw new NotImplementedException(
                    'Can\'t get name from property of type ' . TypeHelper::getType($property)
                );
        }
    }

    private function isCollectedProperty(Stmt $stmt): bool
    {
        return $stmt instanceof Property
            && $this->propertyCollector->hasCollectedResolverCollection($this->getPropertyName($stmt));
    }

    private function getTypeCollectionFromUses(Node $node, string $type): TypeCollection
    {
        $positionOfBrackets = mb_strpos($type, '[');
        $arrayList = $positionOfBrackets ? mb_substr($type, $positionOfBrackets) : '';
        $nestedType = rtrim($type, '[]');
        $namespaceNode = ParentConnectorVisitor::getFirstParentNodeOfType($node, Namespace_::class);

        foreach ($namespaceNode->stmts ?? [] as $use) {
            if (!$use instanceof Use_) {
                continue;
            }

            $useUse = $use->uses[0];
            $useType = (string) $useUse->name;

            if (
                $nestedType === $useType
                || $nestedType === (string) $useUse->alias
                || StringHelper::hasEnding($nestedType, $useType)
            ) {
                if ($arrayList) {
                    return new TypeCollection($this->typeFactory->buildTypeFromString($useType . $arrayList));
                }

                return self::getTypeCollectionFromNode($useUse);
            }
        }

        return new TypeCollection();
    }

    private function assumeIsInSameNamespace(Property $property, string $type): Type
    {
        /** @var Namespace_ $namespaceNode */
        $namespaceNode = ParentConnectorVisitor::getFirstParentNodeOfType($property, Namespace_::class);
        $namespacedType = $namespaceNode->name . "\\$type";

        return $this->typeFactory->buildTypeFromString($namespacedType);
    }
}
