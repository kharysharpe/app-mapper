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

use Hgraca\AppMapper\Core\Port\Logger\StaticLoggerFacade;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\UnknownVariableException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeTypeManagerTrait;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeResolverCollector;
use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\Trait_;

final class TraitNodeStrategy extends AbstractStrategy
{
    use NodeTypeManagerTrait;
    use VariableNameExtractorTrait;

    /**
     * @var TypeResolverCollector
     */
    private $propertyCollector;

    public function __construct(TypeResolverCollector $propertyCollector)
    {
        $this->propertyCollector = $propertyCollector;
    }

    /**
     * @param Node|Trait_ $trait
     */
    public function leaveNode(Node $trait): void
    {
        $this->validateNode($trait);

        $this->addCollectedPropertyResolversToTheirDeclaration($trait);
        $this->propertyCollector->resetCollectedResolvers();
    }

    public static function getNodeTypeHandled(): string
    {
        return Trait_::class;
    }

    /**
     * After collecting app possible class properties, we inject them in their declaration
     *
     * TODO We are only adding properties types in the class itself.
     *      We should fix this by adding them also to the super classes.
     */
    private function addCollectedPropertyResolversToTheirDeclaration(Trait_ $node): void
    {
        foreach ($node->stmts as $property) {
            if ($this->isCollectedProperty($property)) {
                try {
                    self::addTypeResolverCollection(
                        $property,
                        $this->propertyCollector->getCollectedResolverCollection($this->getPropertyName($property))
                    );
                } catch (UnknownVariableException $e) {
                    StaticLoggerFacade::warning(
                        "Silently ignoring a UnknownVariableException.\n"
                        . "The property is not in the collector, so we can't add it to the Property declaration.\n"
                        . $e->getMessage(),
                        [__METHOD__]
                    );
                }
            }
        }
    }

    private function isCollectedProperty(Stmt $stmt): bool
    {
        return $stmt instanceof Property
            && $this->propertyCollector->hasCollectedResolverCollection($this->getPropertyName($stmt));
    }
}
