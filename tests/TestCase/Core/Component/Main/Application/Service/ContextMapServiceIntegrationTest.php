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

namespace Hgraca\ContextMapper\Test\TestCase\Core\Component\Main\Application\Service;

use Hgraca\ContextMapper\Core\Component\Main\Application\Service\ContextMapService;
use Hgraca\ContextMapper\Core\Component\Main\Domain\ContextMap;
use Hgraca\ContextMapper\Core\Port\Configuration\ConfigurationFactoryInterface;
use Hgraca\ContextMapper\Test\Framework\AbstractIntegrationTest;
use Hgraca\ContextMapper\Test\StubProjectSrc\Core\Component\X\Application\Service\XxxAaaService;
use Hgraca\ContextMapper\Test\StubProjectSrc\Core\SharedKernel\Event\AaaEvent;
use Hgraca\ContextMapper\Test\StubProjectSrc\Core\SharedKernel\Event\CccEvent;
use Hgraca\ContextMapper\Test\StubProjectSrc\Core\SharedKernel\Event\DddEvent;
use Hgraca\ContextMapper\Test\StubProjectSrc\Core\SharedKernel\Event\EeeEvent;

final class ContextMapServiceIntegrationTest extends AbstractIntegrationTest
{
    /**
     * @var ContextMap
     */
    private static $contextMap;

    /**
     * This needs to be run inside the test so it counts for coverage.
     * Nevertheless, it will only actually run once.
     */
    public function createContextMap(): void
    {
        if (!self::$contextMap) {
            self::$contextMap = $this->getContextMapService()->createFromConfig(
                $this->getConfigurationFactory()->createConfig(__DIR__ . '/.cmap.yml')
            );
        }
    }

//    /**
//     * @test
//     */
//    public function event_type_is_inferred_correctly_when_injected_but_type_hinted_interface(): void
//    {
//        $this->createContextMap();
//        $this->assertMethodDispatchesEvent(XxxAaaService::class, 'methodC', BbbEvent::class);
//    }
//
//    /**
//     * @test
//     */
//    public function event_type_is_inferred_correctly_when_ternary_operator_is_used(): void
//    {
//        $this->createContextMap();
//        $this->assertMethodDispatchesEvent(XxxAaaService::class, 'methodC', CccEvent::class);
//    }

    /**
     * @test
     */
    public function event_type_is_inferred_correctly_when_variable_is_used(): void
    {
        $this->createContextMap();
        $this->assertMethodDispatchesEvent(XxxAaaService::class, 'methodD', DddEvent::class);
    }

    /**
     * @test
     */
    public function event_type_is_inferred_correctly_when_instantiation_is_used(): void
    {
        $this->createContextMap();
        $this->assertMethodDispatchesEvent(XxxAaaService::class, 'methodE', EeeEvent::class);
    }

    /**
     * @test
     */
    public function event_type_is_inferred_correctly_when_injected_into_method(): void
    {
        $this->createContextMap();
        $this->assertMethodDispatchesEvent(XxxAaaService::class, 'methodF', AaaEvent::class);
    }

    /**
     * @test
     */
    public function event_type_is_inferred_correctly_when_named_constructor(): void
    {
        $this->createContextMap();
        $this->assertMethodDispatchesEvent(XxxAaaService::class, 'methodG', CccEvent::class);
    }

    private function getConfigurationFactory(): ConfigurationFactoryInterface
    {
        return $this->getService(ConfigurationFactoryInterface::class);
    }

    private function getContextMapService(): ContextMapService
    {
        return $this->getService(ContextMapService::class);
    }

    private function assertMethodDispatchesEvent(
        string $dispatcherNodeFqcn,
        string $dispatcherNodeMethodName,
        string $eventFqcn
    ): void {
        $dispatcherNodeFqcn = ltrim($dispatcherNodeFqcn, '\\');
        $eventFqcn = ltrim($eventFqcn, '\\');
        $eventDispatcherNodeList = [];
        foreach (self::$contextMap->getComponentList() as $component) {
            foreach ($component->getEventDispatcherCollection() as $eventDispatcherNode) {
                if (
                    $eventDispatcherNode->getDispatcherClassFqcn() === $dispatcherNodeFqcn
                    && $eventDispatcherNode->getDispatcherMethod() === $dispatcherNodeMethodName
                    && $eventDispatcherNode->getEventFullyQualifiedName() === $eventFqcn
                ) {
                    self::assertTrue(true);

                    return;
                }
                $eventDispatcherNodeList[] =
                    $eventDispatcherNode->getDispatcherClassFqcn()
                    . '::' . $eventDispatcherNode->getDispatcherMethod() . '()'
                    . ' => ' . $eventDispatcherNode->getEventFullyQualifiedName();
            }
        }

        self::fail(
            "The parser did not detect $dispatcherNodeFqcn::$dispatcherNodeMethodName() dispatching event $eventFqcn.\n"
            . "Event dispatcher list detected: \n"
            . implode("\n", $eventDispatcherNodeList)
        );
    }

//    /**
//     * @throws \ReflectionException
//     */
//    private function getClassAst(string $typeAsString): Node
//    {
//        $component = $this->getComponent('X');
//        $astMap = ReflectionService::getProtectedProperty($component->getAstMap(), 'astMap');
//        /** @var NodeCollection $nodeCollection */
//        $nodeCollection = ReflectionService::getProtectedProperty($astMap, 'completeNodeCollection');
//
//        return $nodeCollection->getAstNode($typeAsString);
//    }
//
//    private function getComponent(string $componentName): Component
//    {
//        $componentList = self::$contextMap->getComponentList();
//        foreach ($componentList as $component) {
//            $componentNameList[] = $component->getName();
//            if ($component->getName() === $componentName) {
//                return $component;
//            }
//        }
//
//        throw new TestException(
//            "Component with name $componentName not found. Found component names: "
//            . implode(', ', $componentNameList ?? [])
//        );
//    }
}
