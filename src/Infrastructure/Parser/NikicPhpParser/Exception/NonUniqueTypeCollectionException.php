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

namespace Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception;

use Hgraca\AppMapper\Core\SharedKernel\Exception\AppMapperLogicException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeCollection;
use function array_keys;

final class NonUniqueTypeCollectionException extends AppMapperLogicException
{
    public function __construct(TypeCollection $typeCollection)
    {
        parent::__construct(
            "The type collection contains more than one type: \n"
            . implode("\n", array_keys($typeCollection->toArray()))
        );
    }
}
