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

namespace Hgraca\AppMapper\Test\Framework;

use Hgraca\AppMapper\Test\Framework\Container\ContainerAwareTestTrait;
use Hgraca\AppMapper\Test\Framework\Mock\MockTrait;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * An integration test will test the integration of the application code with the framework, the DB,
 * the delivery mechanism, etc.
 * Usually, for this, we need to get services out of the container, etc. This top class makes it easier.
 * Furthermore, integration tests need to boot the application and therefore they are slower than the Unit tests.
 */
abstract class AbstractIntegrationTest extends KernelTestCase implements AppTestInterface
{
    use AppTestTrait;
    use ContainerAwareTestTrait;
    use MockeryPHPUnitIntegration;
    use MockTrait;

    protected function getContainer(): ContainerInterface
    {
        if (!static::$kernel) {
            self::bootKernel();
        }

        return self::$kernel->getContainer();
    }
}
