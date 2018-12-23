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

use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\UnknownVariableException;
use PhpParser\Node\Expr\Variable;

trait VariableTypeCollectorTrait
{
    private $variableTypeBuffer = [];

    private function collectVariableType(string $variableName, TypeCollection $variableType): void
    {
        $this->variableTypeBuffer[$variableName] = $variableType;
    }

    private function hasCollectedVariableType(string $variableName): bool
    {
        return array_key_exists($variableName, $this->variableTypeBuffer);
    }

    private function getCollectedVariableType(string $variableName): TypeCollection
    {
        if (!$this->hasCollectedVariableType($variableName)) {
            throw new UnknownVariableException($variableName);
        }

        return $this->variableTypeBuffer[$variableName];
    }

    private function resetCollectedVariableTypes(): void
    {
        $this->variableTypeBuffer = [];
    }

    private function getVariableName(Variable $variable): string
    {
        return $variable->name;
    }
}
