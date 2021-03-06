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

final class ClassMethodTestEntity
{
    public function methodWithBaseTypes(
        string $string,
        array $array,
        callable $callable,
        string $stringDefaultEmpty = '',
        int $intDefaultZero = 0,
        float $floatDefaultZero = 0
    ): string {
        return '';
    }

    public function methodWithNullables(
        ?string $nullable,
        ?string $nullableAndDefaultNull = null,
        string $stringDefaultNull = null,
        int $intDefaultNull = null,
        float $floatDefaultNull = null
    ): ?string {
        return null;
    }
}
