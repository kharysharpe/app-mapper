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

namespace Hgraca\ContextMapper\Core\Port\Parser\Node;

interface MethodInterface extends AdapterNodeInterface
{
    public function getCanonicalName(): string;

    public function getReturnTypeNode(): TypeNodeInterface;

    public function getReturnType(): string;

    public function getParameter(int $int): MethodParameterInterface;

    public function isConstructor(): bool;

    public function isPublic(): bool;
}
