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

namespace Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Strategy;

use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\NotImplementedException;
use Hgraca\PhpExtension\Type\TypeHelper;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Property;

trait VariableNameExtractorTrait
{
    private function getVariableName(Variable $variable): string
    {
        return $variable->name;
    }

    private function getPropertyName($property): string
    {
        switch (true) {
            case $property instanceof Property:
                return (string) $property->props[0]->name;
                break;
            case $property instanceof PropertyFetch:
                return (string) $property->name;
                break;
            default:
                throw new NotImplementedException(
                    'Can\'t get name from property of type ' . TypeHelper::getType($property)
                );
        }
    }
}