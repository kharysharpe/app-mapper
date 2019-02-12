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

namespace Hgraca\AppMapper\Test\TestCase\Infrastructure\Parser\NikicPhpParser;

use DateTime;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeCollection;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeDecoratorAccessorTrait;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\PropertyNodeDecorator;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\StmtClassNodeDecorator;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Type;
use Hgraca\AppMapper\Test\Framework\AbstractIntegrationTest;
use Hgraca\AppMapper\Test\StubProjectSrc\Core\Component\X\Application\Service\XxxAaaService;
use Hgraca\AppMapper\Test\StubProjectSrc\Core\Component\X\Application\Service\XxxBbbService;
use Hgraca\AppMapper\Test\StubProjectSrc\Core\Component\X\Domain\AaaEntity;
use Hgraca\AppMapper\Test\StubProjectSrc\Core\Component\X\Domain\AaaEntityParent;
use Hgraca\AppMapper\Test\StubProjectSrc\Core\Component\X\Domain\AaaTrait;
use Hgraca\AppMapper\Test\StubProjectSrc\Core\Component\X\Domain\BbbEntity;
use Hgraca\AppMapper\Test\StubProjectSrc\Core\Component\X\Domain\BbbTrait;
use Hgraca\AppMapper\Test\StubProjectSrc\Core\Component\X\Domain\CccEntity;
use Hgraca\AppMapper\Test\StubProjectSrc\Core\Component\X\Domain\CccTrait;
use Hgraca\AppMapper\Test\StubProjectSrc\Core\Component\X\Domain\ClassMethodTestEntity;
use Hgraca\AppMapper\Test\StubProjectSrc\Core\Port\DummyPort\DummyInterface;
use Hgraca\AppMapper\Test\StubProjectSrc\Core\Port\EventDispatcher\EventDispatcherInterface;
use Hgraca\AppMapper\Test\StubProjectSrc\Core\Port\EventDispatcher\EventInterface;
use Hgraca\AppMapper\Test\StubProjectSrc\Core\SharedKernel\DddTrait;
use Hgraca\AppMapper\Test\StubProjectSrc\Core\SharedKernel\Event\AaaEvent;
use Hgraca\AppMapper\Test\StubProjectSrc\Core\SharedKernel\Event\CccEvent;
use Hgraca\AppMapper\Test\StubProjectSrc\Core\SharedKernel\Event\DddEvent;
use Hgraca\PhpExtension\Reflection\ReflectionHelper;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use function array_keys;
use function json_encode;
use const JSON_PRETTY_PRINT;

final class NodeCollectionIntegrationTest extends AbstractIntegrationTest
{
    use NodeDecoratorAccessorTrait;

    /**
     * @var NodeCollection
     */
    private static $nodeCollection;

    protected function setUp(): void
    {
        $this->createNodeCollection();
    }

    private function createNodeCollection(): void
    {
        if (!self::$nodeCollection) {
            self::$nodeCollection = NodeCollection::constructFromFolder(
                __DIR__ . '/../../../../StubProjectSrc'
            );
            self::$nodeCollection->enhance();
            self::$nodeCollection->resolveAllTypes();
        }
    }

    /**
     * @test
     */
    public function visitors_handle_all_cases_in_test_and_node_collection_is_created(): void
    {
        // if it didnt break, we can assume the visitors can handle all cases in the StubProjectSrc
        self::assertTrue(true);
    }

    /**
     * @test
     *
     * @throws \ReflectionException
     */
    public function property_has_types_from_comment(): void
    {
        $propertyNode = $this->getProperty('eventDispatcher', XxxAaaService::class);
        $propertyTypes = ReflectionHelper::getNestedProperty(
            'attributes.decorator.typeCollection.itemList',
            $propertyNode
        );
        self::assertArrayHasKey(
            DummyInterface::class,
            $propertyTypes,
            "Can't find " . DummyInterface::class . " in \n"
            . json_encode(array_keys($propertyTypes), JSON_PRETTY_PRINT)
        );
        self::assertArrayHasKey(
            EventDispatcherInterface::class,
            $propertyTypes,
            "Can't find " . EventDispatcherInterface::class . " in \n"
            . json_encode(array_keys($propertyTypes), JSON_PRETTY_PRINT)
        );
    }

    /**
     * @test
     *
     * @throws \ReflectionException
     */
    public function assignment_of_array(): void
    {
        $methodNode = $this->getMethod('testArrayUnique', BbbEntity::class);
        $methodTypes = ReflectionHelper::getNestedProperty(
            'stmts.0.expr.expr.attributes.decorator.typeCollection.itemList',
            $methodNode
        );
        self::assertArrayHasKey('array', $methodTypes, implode(', ', array_keys($methodTypes)));
    }

    /**
     * @test
     *
     * @throws \ReflectionException
     */
    public function assignment_of_null(): void
    {
        $methodNode = $this->getMethod('testNull', BbbEntity::class);
        $methodTypes = ReflectionHelper::getNestedProperty(
            'stmts.0.expr.expr.attributes.decorator.typeCollection.itemList',
            $methodNode
        );
        self::assertArrayHasKey('null', $methodTypes, implode(', ', array_keys($methodTypes)));
    }

    /**
     * @test
     *
     * @throws \ReflectionException
     */
    public function assignment_native_function_call(): void
    {
        $methodNode = $this->getMethod('testSprintf', BbbEntity::class);
        $methodTypes = ReflectionHelper::getNestedProperty(
            'stmts.0.expr.var.attributes.decorator.typeCollection.itemList',
            $methodNode
        );
        self::assertArrayHasKey('string', $methodTypes);
    }

    /**
     * @test
     *
     * @throws \ReflectionException
     */
    public function assignment_of_bool(): void
    {
        $methodNode = $this->getMethod('testBool', BbbEntity::class);
        $methodTypes = ReflectionHelper::getNestedProperty(
            'stmts.0.expr.expr.attributes.decorator.typeCollection.itemList',
            $methodNode
        );
        self::assertArrayHasKey('bool', $methodTypes);
    }

    /**
     * @test
     *
     * @throws \ReflectionException
     */
    public function assignment_from_ternary_adds_all_expr_types_to_var(): void
    {
        $methodNode = $this->getMethod('methodC', XxxAaaService::class);
        $methodTypes = ReflectionHelper::getNestedProperty(
            'stmts.0.expr.var.attributes.decorator.typeCollection.itemList',
            $methodNode
        );
        self::assertArrayHasKey(EventInterface::class, $methodTypes);
        self::assertArrayHasKey(CccEvent::class, $methodTypes);

        $methodNode = $this->getMethod('methodJ', XxxAaaService::class);
        $methodTypes = ReflectionHelper::getNestedProperty(
            'stmts.0.expr.var.attributes.decorator.typeCollection.itemList',
            $methodNode
        );
        self::assertArrayHasKey(AaaEvent::class, $methodTypes);
        self::assertArrayHasKey(CccEvent::class, $methodTypes);
    }

    /**
     * @test
     *
     * @throws \ReflectionException
     */
    public function assignment_from_coalesce_adds_all_expr_types_to_var_and_removes_left_null(): void
    {
        $methodNode = $this->getMethod('methodM', XxxAaaService::class);
        $varTypes = ReflectionHelper::getNestedProperty(
            'stmts.0.expr.var.attributes.decorator.typeCollection.itemList',
            $methodNode
        );
        self::assertArrayHasKey(EventInterface::class, $varTypes);
        self::assertArrayHasKey(CccEvent::class, $varTypes);
        self::assertArrayNotHasKey('null', $varTypes);
    }

    /**
     * @test
     *
     * @throws \ReflectionException
     */
    public function var_contains_param_types(): void
    {
        $methodNode = $this->getMethod('methodM', XxxAaaService::class);
        $paramTypes = ReflectionHelper::getNestedProperty(
            'params.0.var.attributes.decorator.typeCollection.itemList',
            $methodNode
        );
        self::assertArrayHasKey(EventInterface::class, $paramTypes);
        self::assertArrayHasKey('null', $paramTypes);

        $varTypes = ReflectionHelper::getNestedProperty(
            'stmts.0.expr.expr.left.attributes.decorator.typeCollection.itemList',
            $methodNode
        );
        self::assertCount(count($paramTypes), $varTypes);
        foreach (array_keys($paramTypes) as $type) {
            self::assertArrayHasKey($type, $varTypes);
        }
    }

    /**
     * @test
     *
     * @throws \ReflectionException
     */
    public function method_return_has_type(): void
    {
        $constructorMethodNode = $this->getMethod('__construct', AaaEntity::class);
        self::assertNull(
            ReflectionHelper::getNestedProperty(
                'returnType',
                $constructorMethodNode
            )
        );
        $methodXxxMethodNode = $this->getMethod('methodXxx', AaaEntity::class);
        self::assertNull(
            ReflectionHelper::getNestedProperty(
                'returnType.attributes.decorator.typeCollection.itemList.void.nodeDecorator',
                $methodXxxMethodNode
            )
        );
        self::assertEquals(
            'void',
            ReflectionHelper::getNestedProperty(
                'returnType.attributes.decorator.typeCollection.itemList.void.typeAsString',
                $methodXxxMethodNode
            )
        );
        $methodYyyMethodNode = $this->getMethod('methodYyy', AaaEntity::class);
        self::assertInstanceOf(
            StmtClassNodeDecorator::class,
            ReflectionHelper::getNestedProperty(
                'returnType.attributes.decorator.typeCollection.itemList.' . BbbEntity::class . '.nodeDecorator',
                $methodYyyMethodNode
            )
        );
        self::assertEquals(
            BbbEntity::class,
            ReflectionHelper::getNestedProperty(
                'returnType.attributes.decorator.typeCollection.itemList.' . BbbEntity::class . '.typeAsString',
                $methodYyyMethodNode
            )
        );
    }

    /**
     * @test
     *
     * @throws \ReflectionException
     */
    public function this_has_type(): void
    {
        $constructorMethodNode = $this->getMethod('__construct', AaaEntity::class);
        self::assertInstanceOf(
            StmtClassNodeDecorator::class,
            ReflectionHelper::getNestedProperty(
                'stmts.0.expr.var.var.attributes.decorator.typeCollection.itemList.' . AaaEntity::class . '.nodeDecorator',
                $constructorMethodNode
            )
        );
        self::assertEquals(
            AaaEntity::class,
            ReflectionHelper::getNestedProperty(
                'stmts.0.expr.var.var.attributes.decorator.typeCollection.itemList.' . AaaEntity::class . '.typeAsString',
                $constructorMethodNode
            )
        );
        $methodXxxMethodNode = $this->getMethod('methodXxx', AaaEntity::class);
        self::assertInstanceOf(
            StmtClassNodeDecorator::class,
            ReflectionHelper::getNestedProperty(
                'stmts.0.expr.expr.var.var.attributes.decorator.typeCollection.itemList.' . AaaEntity::class . '.nodeDecorator',
                $methodXxxMethodNode
            )
        );
        self::assertEquals(
            AaaEntity::class,
            ReflectionHelper::getNestedProperty(
                'stmts.0.expr.expr.var.var.attributes.decorator.typeCollection.itemList.' . AaaEntity::class . '.typeAsString',
                $methodXxxMethodNode
            )
        );
    }

    /**
     * @test
     *
     * @throws \ReflectionException
     */
    public function method_parameters_have_type(): void
    {
        $constructorBbbEntityParameter = $this->getMethodParameter('__construct', 'bbbEntity', AaaEntity::class);
        self::assertInstanceOf(
            StmtClassNodeDecorator::class,
            ReflectionHelper::getNestedProperty(
                'attributes.decorator.typeCollection.itemList.' . BbbEntity::class . '.nodeDecorator',
                $constructorBbbEntityParameter
            )
        );
        self::assertEquals(
            BbbEntity::class,
            ReflectionHelper::getNestedProperty(
                'attributes.decorator.typeCollection.itemList.' . BbbEntity::class . '.typeAsString',
                $constructorBbbEntityParameter
            )
        );
    }

    /**
     * @test
     *
     * @throws \ReflectionException
     */
    public function parameter_has_nullable_hint_and_default_types(): void
    {
        $methodNode = $this->getMethod('methodWithNullables', ClassMethodTestEntity::class);

        $methodTypes = ReflectionHelper::getNestedProperty(
            'params.0.attributes.decorator.typeCollection.itemList',
            $methodNode
        );
        self::assertArrayHasKey('string', $methodTypes);
        self::assertArrayHasKey('null', $methodTypes);

        $methodTypes = ReflectionHelper::getNestedProperty(
            'params.1.attributes.decorator.typeCollection.itemList',
            $methodNode
        );
        self::assertArrayHasKey('string', $methodTypes);
        self::assertArrayHasKey('null', $methodTypes);

        $methodTypes = ReflectionHelper::getNestedProperty(
            'params.2.attributes.decorator.typeCollection.itemList',
            $methodNode
        );
        self::assertArrayHasKey('string', $methodTypes);
        self::assertArrayHasKey('null', $methodTypes);
    }

    /**
     * @test
     *
     * @throws \ReflectionException
     */
    public function class_properties_have_type(): void
    {
        self::assertInstanceOf(
            StmtClassNodeDecorator::class,
            ReflectionHelper::getNestedProperty(
                'attributes.decorator.typeCollection.itemList.' . BbbEntity::class . '.nodeDecorator',
                $this->getProperty('bbbEntity', AaaEntity::class)
            )
        );
        self::assertEquals(
            BbbEntity::class,
            ReflectionHelper::getNestedProperty(
                'attributes.decorator.typeCollection.itemList.' . BbbEntity::class . '.typeAsString',
                $this->getProperty('bbbEntity', AaaEntity::class)
            )
        );
        self::assertNull(
            ReflectionHelper::getNestedProperty(
                'attributes.decorator.typeCollection.itemList.DateTime.nodeDecorator',
                $this->getProperty('createdAt', AaaEntity::class)
            )
        );
        self::assertEquals(
            DateTime::class,
            ReflectionHelper::getNestedProperty(
                'attributes.decorator.typeCollection.itemList.DateTime.typeAsString',
                $this->getProperty('createdAt', AaaEntity::class)
            )
        );
    }

    /**
     * @test
     *
     * @throws \ReflectionException
     */
    public function class_properties_inherited_from_parent_have_type(): void
    {
        /** @var StmtClassNodeDecorator $classNodeDecorator */
        $classNodeDecorator = $this->getNodeDecorator(
            self::$nodeCollection->getAstNode(AaaEntity::class)
        );

        /** @var PropertyNodeDecorator $propertyNodeDecorator */
        $propertyNodeDecorator = $this->getNodeDecorator(
            $this->getProperty('aaaEntityParentProperty', AaaEntityParent::class)
        );
        self::assertTrue(
            $classNodeDecorator->getPropertyTypeCollection($propertyNodeDecorator)->hasType(CccEntity::class)
        );
    }

    /**
     * @test
     *
     * @throws \ReflectionException
     */
    public function class_properties_inherited_from_trait_have_type(): void
    {
        /** @var StmtClassNodeDecorator $classNodeDecorator */
        $classNodeDecorator = $this->getNodeDecorator(
            self::$nodeCollection->getAstNode(AaaEntity::class)
        );

        /** @var PropertyNodeDecorator $propertyNodeDecorator */
        $propertyNodeDecorator = $this->getNodeDecorator(
            $this->getProperty('aaaTraitProperty', AaaTrait::class)
        );
        self::assertTrue(
            $classNodeDecorator->getPropertyTypeCollection($propertyNodeDecorator)->hasType(CccEntity::class)
        );

        /** @var PropertyNodeDecorator $propertyNodeDecorator */
        $propertyNodeDecorator = $this->getNodeDecorator(
            $this->getProperty('bbbTraitProperty', BbbTrait::class)
        );
        self::assertTrue(
            $classNodeDecorator->getPropertyTypeCollection($propertyNodeDecorator)->hasType(CccEntity::class)
        );

        /** @var PropertyNodeDecorator $propertyNodeDecorator */
        $propertyNodeDecorator = $this->getNodeDecorator(
            $this->getProperty('cccTraitProperty', CccTrait::class)
        );
        self::assertTrue(
            $classNodeDecorator->getPropertyTypeCollection($propertyNodeDecorator)->hasType(CccEntity::class)
        );

        /** @var PropertyNodeDecorator $propertyNodeDecorator */
        $propertyNodeDecorator = $this->getNodeDecorator(
            $this->getProperty('dddTraitProperty', DddTrait::class)
        );
        self::assertTrue(
            $classNodeDecorator->getPropertyTypeCollection($propertyNodeDecorator)->hasType(CccEntity::class)
        );
    }

    /**
     * @test
     *
     * @throws \ReflectionException
     */
    public function instantiation_has_type(): void
    {
        $constructorMethodNode = $this->getMethod('__construct', AaaEntity::class);
        self::assertNull(
            ReflectionHelper::getNestedProperty(
                'stmts.1.expr.expr.attributes.decorator.typeCollection.itemList.DateTime.nodeDecorator',
                $constructorMethodNode
            )
        );
        self::assertEquals(
            DateTime::class,
            ReflectionHelper::getNestedProperty(
                'stmts.1.expr.expr.attributes.decorator.typeCollection.itemList.DateTime.typeAsString',
                $constructorMethodNode
            )
        );
        $methodXxxMethodNode = $this->getMethod('methodXxx', AaaEntity::class);
        self::assertInstanceOf(
            StmtClassNodeDecorator::class,
            ReflectionHelper::getNestedProperty(
                'stmts.0.expr.expr.var.var.attributes.decorator.typeCollection.itemList.' . AaaEntity::class . '.nodeDecorator',
                $methodXxxMethodNode
            )
        );
        self::assertEquals(
            AaaEntity::class,
            ReflectionHelper::getNestedProperty(
                'stmts.0.expr.expr.var.var.attributes.decorator.typeCollection.itemList.' . AaaEntity::class . '.typeAsString',
                $methodXxxMethodNode
            )
        );
    }

    /**
     * @test
     *
     * @throws \ReflectionException
     */
    public function variable_has_type(): void
    {
        $methodNode = $this->getMethod('methodD', XxxAaaService::class);
        self::assertInstanceOf(
            StmtClassNodeDecorator::class,
            ReflectionHelper::getNestedProperty(
                'stmts.1.expr.args.0.value.attributes.decorator.typeCollection.itemList.' . DddEvent::class . '.nodeDecorator',
                $methodNode
            )
        );
        self::assertEquals(
            DddEvent::class,
            ReflectionHelper::getNestedProperty(
                'stmts.1.expr.args.0.value.attributes.decorator.typeCollection.itemList.' . DddEvent::class . '.typeAsString',
                $methodNode
            )
        );
    }

    /**
     * @test
     *
     * @throws \ReflectionException
     */
    public function static_method_call_has_type(): void
    {
        $methodNode = $this->getMethod('methodK', XxxAaaService::class);
        self::assertInstanceOf(
            StmtClassNodeDecorator::class,
            ReflectionHelper::getNestedProperty(
                'stmts.0.expr.expr.attributes.decorator.typeCollection.itemList.' . CccEvent::class . '.nodeDecorator',
                $methodNode
            )
        );
        self::assertEquals(
            CccEvent::class,
            ReflectionHelper::getNestedProperty(
                'stmts.0.expr.expr.attributes.decorator.typeCollection.itemList.' . CccEvent::class . '.typeAsString',
                $methodNode
            )
        );
    }

    /**
     * @test
     *
     * @throws \ReflectionException
     */
    public function variable_has_type_assigned_from_static_method_call(): void
    {
        $methodNode = $this->getMethod('methodK', XxxAaaService::class);
        self::assertInstanceOf(
            StmtClassNodeDecorator::class,
            ReflectionHelper::getNestedProperty(
                'stmts.1.expr.args.0.value.attributes.decorator.typeCollection.itemList.' . CccEvent::class . '.nodeDecorator',
                $methodNode
            )
        );
        self::assertEquals(
            CccEvent::class,
            ReflectionHelper::getNestedProperty(
                'stmts.1.expr.args.0.value.attributes.decorator.typeCollection.itemList.' . CccEvent::class . '.typeAsString',
                $methodNode
            )
        );
    }

    /**
     * @test
     *
     * @throws \ReflectionException
     */
    public function method_call_has_type(): void
    {
        $methodNode = $this->getMethod('methodC', XxxBbbService::class);
        self::assertInstanceOf(
            StmtClassNodeDecorator::class,
            ReflectionHelper::getNestedProperty(
                'stmts.0.expr.var.attributes.decorator.typeCollection.itemList.' . CccEvent::class . '.nodeDecorator',
                $methodNode
            )
        );
        self::assertEquals(
            CccEvent::class,
            ReflectionHelper::getNestedProperty(
                'stmts.0.expr.var.attributes.decorator.typeCollection.itemList.' . CccEvent::class . '.typeAsString',
                $methodNode
            )
        );
    }

    /**
     * @test
     *
     * @throws \ReflectionException
     */
    public function variable_has_type_assigned_from_method_call(): void
    {
        $methodNode = $this->getMethod('methodC', XxxBbbService::class);
        self::assertInstanceOf(
            StmtClassNodeDecorator::class,
            ReflectionHelper::getNestedProperty(
                'stmts.0.expr.var.attributes.decorator.typeCollection.itemList.' . CccEvent::class . '.nodeDecorator',
                $methodNode
            )
        );
        self::assertEquals(
            CccEvent::class,
            ReflectionHelper::getNestedProperty(
                'stmts.0.expr.var.attributes.decorator.typeCollection.itemList.' . CccEvent::class . '.typeAsString',
                $methodNode
            )
        );
    }

    /**
     * @test
     *
     * @throws \ReflectionException
     */
    public function local_method_call_has_type(): void
    {
        $methodNode = $this->getMethod('methodD', XxxBbbService::class);
        self::assertInstanceOf(
            StmtClassNodeDecorator::class,
            ReflectionHelper::getNestedProperty(
                'stmts.0.expr.expr.attributes.decorator.typeCollection.itemList.' . CccEvent::class . '.nodeDecorator',
                $methodNode
            )
        );
        self::assertEquals(
            CccEvent::class,
            ReflectionHelper::getNestedProperty(
                'stmts.0.expr.expr.attributes.decorator.typeCollection.itemList.' . CccEvent::class . '.typeAsString',
                $methodNode
            )
        );
    }

    /**
     * @test
     *
     * @throws \ReflectionException
     */
    public function variable_has_type_assigned_from_local_method_call(): void
    {
        $methodNode = $this->getMethod('methodD', XxxBbbService::class);
        self::assertInstanceOf(
            StmtClassNodeDecorator::class,
            ReflectionHelper::getNestedProperty(
                'stmts.0.expr.expr.attributes.decorator.typeCollection.itemList.' . CccEvent::class . '.nodeDecorator',
                $methodNode
            )
        );
        self::assertEquals(
            CccEvent::class,
            ReflectionHelper::getNestedProperty(
                'stmts.0.expr.expr.attributes.decorator.typeCollection.itemList.' . CccEvent::class . '.typeAsString',
                $methodNode
            )
        );
    }

    /**
     * @test
     *
     * @throws \ReflectionException
     */
    public function foreach_assigns_array_nested_types_to_variable(): void
    {
        $methodNode = $this->getMethod('testForeach', XxxBbbService::class);
        self::assertArrayHasKey(
            'array',
            ReflectionHelper::getNestedProperty(
                'stmts.0.expr.attributes.decorator.typeCollection.itemList',
                $methodNode
            )
        );
        self::assertEquals(
            EventInterface::class,
            ReflectionHelper::getNestedProperty(
                'stmts.0.expr.attributes.decorator.typeCollection.itemList.array.nestedType.typeAsString',
                $methodNode
            )
        );
        self::assertArrayHasKey(
            'int',
            ReflectionHelper::getNestedProperty(
                'stmts.0.keyVar.attributes.decorator.typeCollection.itemList',
                $methodNode
            )
        );
        self::assertArrayHasKey(
            'string',
            ReflectionHelper::getNestedProperty(
                'stmts.0.keyVar.attributes.decorator.typeCollection.itemList',
                $methodNode
            )
        );
        self::assertArrayHasKey(
            EventInterface::class,
            ReflectionHelper::getNestedProperty(
                'stmts.0.valueVar.attributes.decorator.typeCollection.itemList',
                $methodNode
            )
        );
    }

    /**
     * @test
     *
     * @throws \ReflectionException
     */
    public function unknown_property_fetch_should_have_type_unknown(): void
    {
        $methodNode = $this->getMethod('testUnknownProperties', AaaEntity::class);
        self::assertArrayHasKey(
            Type::UNKNOWN,
            ReflectionHelper::getNestedProperty(
                'stmts.0.expr.expr.attributes.decorator.typeCollection.itemList',
                $methodNode
            )
        );
        self::assertArrayHasKey(
            Type::UNKNOWN,
            ReflectionHelper::getNestedProperty(
                'stmts.0.expr.var.attributes.decorator.typeCollection.itemList',
                $methodNode
            )
        );
    }

    private function getProperty(string $propertyName, string $classFqcn): Property
    {
        /** @var Class_ $classNode */
        $classNode = self::$nodeCollection->getAstNode($classFqcn);
        foreach ($classNode->stmts as $stmt) {
            if (
                $stmt instanceof Property
                && (string) $stmt->props[0]->name === $propertyName
            ) {
                return $stmt;
            }
        }

        self::fail("Could not find property with name $propertyName in class node $classFqcn");
    }

    private function getMethod(string $methodName, string $classFqcn): ClassMethod
    {
        /** @var Class_ $classNode */
        $classNode = self::$nodeCollection->getAstNode($classFqcn);
        foreach ($classNode->stmts as $stmt) {
            if (
                $stmt instanceof ClassMethod
                && (string) $stmt->name === $methodName
            ) {
                return $stmt;
            }
        }

        self::fail("Could not find method with name $methodName in class node $classFqcn");
    }

    private function getMethodParameter(string $methodName, string $parameterName, string $classFqcn): Param
    {
        $methodNode = $this->getMethod($methodName, $classFqcn);
        foreach ($methodNode->params as $param) {
            if (
                $param instanceof Param
                && (string) $param->var->name === $parameterName
            ) {
                return $param;
            }
        }

        self::fail("Could not find parameter with name $ in method $methodName of class node $classFqcn");
    }
}
