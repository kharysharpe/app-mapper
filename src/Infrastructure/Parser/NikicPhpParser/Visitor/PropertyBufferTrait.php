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

use Hgraca\AppMapper\Core\SharedKernel\Exception\NotImplementedException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\UnknownPropertyException;
use Hgraca\PhpExtension\Type\TypeHelper;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;

trait PropertyBufferTrait
{
    private $propertyTypeBuffer = [];

    private function addPropertyTypeToBuffer(string $propertyName, TypeCollection $propertyType): void
    {
        $this->propertyTypeBuffer[$propertyName] = $propertyType;
    }

    private function hasPropertyTypeInBuffer(string $propertyName): bool
    {
        return array_key_exists($propertyName, $this->propertyTypeBuffer);
    }

    private function getPropertyTypeFromBuffer(string $propertyName): TypeCollection
    {
        if (!$this->hasPropertyTypeInBuffer($propertyName)) {
            throw new UnknownPropertyException($propertyName);
        }

        return $this->propertyTypeBuffer[$propertyName];
    }

    private function resetPropertyTypeBuffer(): void
    {
        $this->propertyTypeBuffer = [];
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

    private function addPropertiesTypeToTheirDeclaration(Class_ $node): void
    {
        // After collecting app possible class properties, we inject them in their declaration
        foreach ($node->stmts as $property) {
            if (
                $property instanceof Property
                && $this->hasPropertyTypeInBuffer($propertyName = $this->getPropertyName($property))
            ) {
                $this->addTypeCollectionToNode($property, $this->getPropertyTypeFromBuffer($propertyName));
            }
        }
        $this->resetPropertyTypeBuffer();
    }
}
