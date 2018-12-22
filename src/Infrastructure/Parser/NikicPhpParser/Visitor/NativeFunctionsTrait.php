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

namespace Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use function array_key_exists;

trait NativeFunctionsTrait
{
    private $functionList = [
        'sprintf' => [
            'return' => 'string',
        ],
        'array_unique' => [
            'return' => 'array',
        ],
    ];

    private function isNative(FuncCall $funcCall): bool
    {
        if ($funcCall->name instanceof Name) {
            $name = (string) $funcCall->name;
        } elseif (property_exists($funcCall->name, 'name')) {
            $name = $funcCall->name->name;
        }

        return array_key_exists($name, $this->functionList);
    }

    private function getReturnType(FuncCall $funcCall): string
    {
        return $this->functionList[(string) $funcCall->name]['return'];
    }
}
