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

namespace Hgraca\AppMapper\Test\StubProjectSrc\Core\Component\X\Domain;

use DateTime;
use function array_unique;

final class BbbEntity
{
    public static function namedConstructor(): self
    {
        return new self();
    }

    /**
     * @throws \Exception
     */
    public function methodXxx(): DateTime
    {
        return new DateTime();
    }

    /**
     * @throws \Exception
     */
    public function methodYyy(): AaaEntity
    {
        return new AaaEntity($this);
    }

    public function testSprintf(): void
    {
        $var = sprintf('something-%s.%s', 'a', 'b');
    }

    public function testBool(): void
    {
        $var = true;
    }

    public function testArrayUnique(): void
    {
        $var = array_unique([]);
    }

    public function testNull(): void
    {
        $var = null;
    }
}
