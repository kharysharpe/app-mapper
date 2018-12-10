<?php

declare(strict_types=1);

/*
 * This file is part of the Context Mapper application,
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

namespace Hgraca\ContextMapper\Test\TestCase\Infrastructure\Parser\NikicPhpParser;

use DateTime;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\NodeCollection;
use Hgraca\ContextMapper\Test\Framework\AbstractIntegrationTest;
use Hgraca\ContextMapper\Test\StubProjectSrc\Core\Component\X\Application\Service\XxxAaaService;
use Hgraca\ContextMapper\Test\StubProjectSrc\Core\Component\X\Domain\AaaEntity;
use Hgraca\ContextMapper\Test\StubProjectSrc\Core\Component\X\Domain\BbbEntity;
use Hgraca\ContextMapper\Test\StubProjectSrc\Core\SharedKernel\Event\DddEvent;
use Hgraca\PhpExtension\Reflection\ReflectionService;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;

final class NodeCollectionIntegrationTest extends AbstractIntegrationTest
{
    /**
     * @var NodeCollection
     */
    private static $nodeCollection;

    /**
     * This needs to be run inside the test so it counts for coverage.
     * Nevertheless, it will only actually run once.
     */
    private function createNodeCollection(): void
    {
        if (!self::$nodeCollection) {
            self::$nodeCollection = NodeCollection::constructFromFolder(
                __DIR__ . '/../../../../StubProjectSrc/Core/Component/X'
            );
            self::$nodeCollection->enhance();
        }
    }

    /**
     * @test
     *
     * @throws \ReflectionException
     */
    public function method_return_has_type(): void
    {
        $this->createNodeCollection();
        $constructorMethodNode = $this->getMethod('__construct', AaaEntity::class);
        self::assertNull(
            ReflectionService::getNestedProperty(
                'returnType',
                $constructorMethodNode
            )
        );
        $methodXxxMethodNode = $this->getMethod('methodXxx', AaaEntity::class);
        self::assertNull(
            ReflectionService::getNestedProperty(
                'returnType.attributes.Type.ast',
                $methodXxxMethodNode
            )
        );
        self::assertEquals(
            'void',
            ReflectionService::getNestedProperty(
                'returnType.attributes.Type.typeAsString',
                $methodXxxMethodNode
            )
        );
        $methodYyyMethodNode = $this->getMethod('methodYyy', AaaEntity::class);
        self::assertInstanceOf(
            Class_::class,
            ReflectionService::getNestedProperty(
                'returnType.attributes.Type.ast',
                $methodYyyMethodNode
            )
        );
        self::assertEquals(
            BbbEntity::class,
            ReflectionService::getNestedProperty(
                'returnType.attributes.Type.typeAsString',
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
        $this->createNodeCollection();
        $constructorMethodNode = $this->getMethod('__construct', AaaEntity::class);
        self::assertInstanceOf(
            Class_::class,
            ReflectionService::getNestedProperty(
                'stmts.0.expr.var.var.attributes.Type.ast',
                $constructorMethodNode
            )
        );
        self::assertEquals(
            AaaEntity::class,
            ReflectionService::getNestedProperty(
                'stmts.0.expr.var.var.attributes.Type.typeAsString',
                $constructorMethodNode
            )
        );
        $methodXxxMethodNode = $this->getMethod('methodXxx', AaaEntity::class);
        self::assertInstanceOf(
            Class_::class,
            ReflectionService::getNestedProperty(
                'stmts.0.expr.expr.var.var.attributes.Type.ast',
                $methodXxxMethodNode
            )
        );
        self::assertEquals(
            AaaEntity::class,
            ReflectionService::getNestedProperty(
                'stmts.0.expr.expr.var.var.attributes.Type.typeAsString',
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
        $this->createNodeCollection();
        $constructorBbbEntityParameter = $this->getMethodParameter('__construct', 'bbbEntity', AaaEntity::class);
        self::assertInstanceOf(
            Class_::class,
            ReflectionService::getNestedProperty(
                'attributes.Type.ast',
                $constructorBbbEntityParameter
            )
        );
        self::assertEquals(
            BbbEntity::class,
            ReflectionService::getNestedProperty(
                'attributes.Type.typeAsString',
                $constructorBbbEntityParameter
            )
        );
    }

    /**
     * @test
     *
     * @throws \ReflectionException
     */
    public function class_properties_have_type(): void
    {
        $this->createNodeCollection();
        self::assertInstanceOf(
            Class_::class,
            ReflectionService::getNestedProperty(
                'attributes.Type.ast',
                $this->getProperty('bbbEntity', AaaEntity::class)
            )
        );
        self::assertEquals(
            BbbEntity::class,
            ReflectionService::getNestedProperty(
                'attributes.Type.typeAsString',
                $this->getProperty('bbbEntity', AaaEntity::class)
            )
        );
        self::assertNull(
            ReflectionService::getNestedProperty(
                'attributes.Type.ast',
                $this->getProperty('createdAt', AaaEntity::class)
            )
        );
        self::assertEquals(
            DateTime::class,
            ReflectionService::getNestedProperty(
                'attributes.Type.typeAsString',
                $this->getProperty('createdAt', AaaEntity::class)
            )
        );
    }

    /**
     * @test
     *
     * @throws \ReflectionException
     */
    public function instantiation_have_type(): void
    {
        $this->createNodeCollection();
        $constructorMethodNode = $this->getMethod('__construct', AaaEntity::class);
        self::assertNull(
            ReflectionService::getNestedProperty(
                'stmts.1.expr.expr.attributes.Type.ast',
                $constructorMethodNode
            )
        );
        self::assertEquals(
            DateTime::class,
            ReflectionService::getNestedProperty(
                'stmts.1.expr.expr.attributes.Type.typeAsString',
                $constructorMethodNode
            )
        );
        $methodXxxMethodNode = $this->getMethod('methodXxx', AaaEntity::class);
        self::assertInstanceOf(
            Class_::class,
            ReflectionService::getNestedProperty(
                'stmts.0.expr.expr.var.var.attributes.Type.ast',
                $methodXxxMethodNode
            )
        );
        self::assertEquals(
            AaaEntity::class,
            ReflectionService::getNestedProperty(
                'stmts.0.expr.expr.var.var.attributes.Type.typeAsString',
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
        $this->createNodeCollection();
        $methodNode = $this->getMethod('methodD', XxxAaaService::class);
        self::assertNull(
            ReflectionService::getNestedProperty(
                'stmts.1.expr.args.0.value.attributes.Type.ast',
                $methodNode
            )
        );
        self::assertEquals(
            DddEvent::class,
            ReflectionService::getNestedProperty(
                'stmts.1.expr.args.0.value.attributes.Type.typeAsString',
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
