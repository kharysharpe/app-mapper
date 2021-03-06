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

namespace Hgraca\AppMapper\Test\TestCase\Core\Component\Main\Application\Service;

use Hgraca\AppMapper\Core\Component\Main\Application\Service\AppMapService;
use Hgraca\AppMapper\Core\Component\Main\Domain\AppMap;
use Hgraca\AppMapper\Core\Port\Configuration\ConfigurationFactoryInterface;
use Hgraca\AppMapper\Test\Framework\AbstractIntegrationTest;
use Hgraca\AppMapper\Test\StubProjectSrc\Core\Component\X\Application\Service\XxxAaaService;
use Hgraca\AppMapper\Test\StubProjectSrc\Core\SharedKernel\Event\AaaEvent;
use Hgraca\AppMapper\Test\StubProjectSrc\Core\SharedKernel\Event\BbbEvent;
use Hgraca\AppMapper\Test\StubProjectSrc\Core\SharedKernel\Event\CccEvent;
use Hgraca\AppMapper\Test\StubProjectSrc\Core\SharedKernel\Event\DddEvent;
use Hgraca\AppMapper\Test\StubProjectSrc\Core\SharedKernel\Event\EeeEvent;

final class AppMapServiceIntegrationTest extends AbstractIntegrationTest
{
    /**
     * @var AppMap
     */
    private static $appMap;

    protected function setUp(): void
    {
        $this->createAppmap();
    }

    /**
     * This needs to be run inside the test so it counts for coverage.
     * Nevertheless, it will only actually run once.
     */
    public function createAppmap(): void
    {
        if (!self::$appMap) {
            self::$appMap = $this->getAppMapService()->createFromConfig(
                $this->getConfigurationFactory()->createConfig(__DIR__ . '/.appmap.yml')
            );
        }
    }

    /**
     * @test
     */
    public function event_type_is_inferred_correctly_when_injected_but_type_hinted_interface(): void
    {
        $this->assertMethodDispatchesEvent(XxxAaaService::class, 'methodC', BbbEvent::class);
    }

    /**
     * @test
     */
    public function event_type_is_inferred_correctly_when_ternary_operator_is_used(): void
    {
        $this->assertMethodDispatchesEvent(XxxAaaService::class, 'methodC', CccEvent::class);
    }

    /**
     * @test
     */
    public function event_type_is_inferred_correctly_when_variable_is_used(): void
    {
        $this->assertMethodDispatchesEvent(XxxAaaService::class, 'methodD', DddEvent::class);
    }

    /**
     * @test
     */
    public function event_type_is_inferred_correctly_when_instantiation_is_used(): void
    {
        $this->assertMethodDispatchesEvent(XxxAaaService::class, 'methodE', EeeEvent::class);
    }

    /**
     * @test
     */
    public function event_type_is_inferred_correctly_when_injected_into_method(): void
    {
        $this->assertMethodDispatchesEvent(XxxAaaService::class, 'methodF', AaaEvent::class);
    }

    /**
     * @test
     */
    public function event_type_is_inferred_correctly_when_named_constructor(): void
    {
        $this->assertMethodDispatchesEvent(XxxAaaService::class, 'methodG', CccEvent::class);
    }

    private function getConfigurationFactory(): ConfigurationFactoryInterface
    {
        return $this->getService(ConfigurationFactoryInterface::class);
    }

    private function getAppMapService(): AppMapService
    {
        return $this->getService(AppMapService::class);
    }

    private function assertMethodDispatchesEvent(
        string $dispatcherNodeFqcn,
        string $dispatcherNodeMethodName,
        string $eventFqcn
    ): void {
        $dispatcherNodeFqcn = ltrim($dispatcherNodeFqcn, '\\');
        $eventFqcn = ltrim($eventFqcn, '\\');
        $eventDispatcherNodeList = [];
        foreach (self::$appMap->getComponentList() as $component) {
            foreach ($component->getEventDispatcherCollection() as $eventDispatcherNode) {
                if (
                    $eventDispatcherNode->getDispatcherClassFqcn() === $dispatcherNodeFqcn
                    && $eventDispatcherNode->getDispatcherMethod() === $dispatcherNodeMethodName
                    && $eventDispatcherNode->dispatches($eventFqcn)
                ) {
                    self::assertTrue(true);

                    return;
                }
                $eventDispatcherNodeList[] =
                    $eventDispatcherNode->getDispatcherClassFqcn()
                    . '::' . $eventDispatcherNode->getDispatcherMethod() . '()'
                    . ' => ' . implode(' | ', $eventDispatcherNode->getEventTypeList());
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
//        $astMap = ReflectionHelper::getProtectedProperty($component->getAstMap(), 'astMap');
//        /** @var NodeCollection $nodeCollection */
//        $nodeCollection = ReflectionHelper::getProtectedProperty($astMap, 'completeNodeCollection');
//
//        return $nodeCollection->getAstNode($typeAsString);
//    }
//
//    private function getComponent(string $componentName): Component
//    {
//        $componentList = self::$appMap->getComponentList();
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
