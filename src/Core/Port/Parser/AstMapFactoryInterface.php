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

namespace Hgraca\AppMapper\Core\Port\Parser;

use Hgraca\AppMapper\Core\Port\Configuration\ComponentDto;

/**
 * This interface makes it possible to instantiate and AstMapInterface in the Core, without specifying the actual
 * class instantiated.
 */
interface AstMapFactoryInterface
{
    public function constructFromPath(string $path): AstMapInterface;

    public function constructFromComponentDtoList(ComponentDto ...$componentDtoList): AstMapInterface;
}
