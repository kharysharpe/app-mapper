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
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\PropertyFetch;

final class PropertyFetchNodeStrategy extends AbstractStrategy
{
    use NodeTypeManagerTrait;
    use VariableNameExtractorTrait;

    private $propertyCollector;

    public function __construct(TypeResolverCollector $propertyCollector)
    {
        $this->propertyCollector = $propertyCollector;
    }

    /**
     * @param Node|PropertyFetch $propertyFetchNode
     */
    public function enterNode(Node $propertyFetchNode): void
    {
        $this->validateNode($propertyFetchNode);

        $parentNode = $propertyFetchNode->getAttribute('parentNode');

        if ($parentNode instanceof Assign && $propertyFetchNode === $parentNode->var) {
            return;
        }

        $this->addCollectedPropertyFetchResolver($propertyFetchNode);
    }

    public static function getNodeTypeHandled(): string
    {
        return PropertyFetch::class;
    }

    /**
     * TODO We are only adding properties types in the class itself.
     *      We should fix this by adding them also to the super classes.
     */
    private function addCollectedPropertyFetchResolver(PropertyFetch $propertyFetch): void
    {
        try {
            self::addTypeResolverCollection(
                $propertyFetch,
                $this->propertyCollector->getCollectedResolverCollection($this->getPropertyName($propertyFetch))
            );
        } catch (UnknownVariableException $e) {
            StaticLoggerFacade::warning(
                "Silently ignoring a UnknownVariableException.\n"
                . "The property is not in the collector, so we can't add it to the PropertyFetch.\n"
                . $e->getMessage(),
                [__METHOD__]
            );
        }
    }
}
