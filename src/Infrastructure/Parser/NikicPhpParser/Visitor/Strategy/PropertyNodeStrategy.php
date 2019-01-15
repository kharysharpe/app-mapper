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

use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeTypeManagerTrait;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\ParentConnectorVisitor;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Type;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeCollection;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeFactory;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeResolverCollector;
use Hgraca\PhpExtension\String\StringHelper;
use Hgraca\PhpExtension\Type\TypeHelper;
use PhpParser\Node;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Use_;

final class PropertyNodeStrategy extends AbstractStrategy
{
    use NodeTypeManagerTrait;
    use VariableNameExtractorTrait;

    /**
     * @var TypeFactory
     */
    private $typeFactory;

    private $propertyCollector;

    public function __construct(TypeFactory $typeFactory, TypeResolverCollector $propertyCollector)
    {
        $this->typeFactory = $typeFactory;
        $this->propertyCollector = $propertyCollector;
    }

    /**
     * @param Node|Property $property
     */
    public function enterNode(Node $property): void
    {
        $this->validateNode($property);

        $resolver = function () use ($property): TypeCollection {
            $typeCollection = new TypeCollection();

            foreach ($property->getAttribute('comments') ?? [] as $comment) {
                foreach (StringHelper::extractFromBetween('@var ', "\n", $comment->getText()) as $typeList) {
                    foreach (explode('|', $typeList) as $type) {
                        if (TypeHelper::isNativeType($type)) {
                            $typeCollection = $typeCollection->addType(new Type($type));
                            continue;
                        }

                        $typeCollectionFromUses = $this->getTypeCollectionFromUses($property, $type);

                        if (!$typeCollectionFromUses->isEmpty()) {
                            $typeCollection = $typeCollection->addTypeCollection($typeCollectionFromUses);
                            continue;
                        }

                        $typeCollection = $typeCollection->addType(
                            $this->assumeIsInSameNamespace($property, $type)
                        );
                    }
                }
            }

            return $typeCollection;
        };

        self::addTypeResolver($property, $resolver);
        $this->propertyCollector->collectResolver($this->getPropertyName($property), $resolver);
    }

    public static function getNodeTypeHandled(): string
    {
        return Property::class;
    }

    private function getTypeCollectionFromUses(Node $node, string $type): TypeCollection
    {
        $positionOfBrackets = mb_strpos($type, '[');
        $arrayList = $positionOfBrackets ? mb_substr($type, $positionOfBrackets) : '';
        $nestedType = rtrim($type, '[]');
        $namespaceNode = ParentConnectorVisitor::getFirstParentNodeOfType($node, Namespace_::class);

        foreach ($namespaceNode->stmts ?? [] as $use) {
            if (!$use instanceof Use_) {
                continue;
            }

            $useUse = $use->uses[0];
            $useType = (string) $useUse->name;

            if (
                $nestedType === $useType
                || $nestedType === (string) $useUse->alias
                || StringHelper::hasEnding($nestedType, $useType)
            ) {
                if ($arrayList) {
                    return new TypeCollection($this->typeFactory->buildTypeFromString($useType . $arrayList));
                }

                return self::getTypeCollectionFromNode($useUse);
            }
        }

        return new TypeCollection();
    }

    private function assumeIsInSameNamespace(Property $property, string $type): Type
    {
        /** @var Namespace_ $namespaceNode */
        $namespaceNode = ParentConnectorVisitor::getFirstParentNodeOfType($property, Namespace_::class);
        $namespacedType = $namespaceNode->name . "\\$type";

        return $this->typeFactory->buildTypeFromString($namespacedType);
    }
}
