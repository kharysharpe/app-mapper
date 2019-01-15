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
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeCollection;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeFactory;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeResolverCollector;
use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Param;

final class VariableNodeStrategy extends AbstractStrategy
{
    use NodeTypeManagerTrait;
    use VariableNameExtractorTrait;

    /**
     * @var TypeFactory
     */
    private $typeFactory;

    private $variableCollector;

    public function __construct(TypeFactory $typeFactory, TypeResolverCollector $variableCollector)
    {
        $this->typeFactory = $typeFactory;
        $this->variableCollector = $variableCollector;
    }

    /**
     * @param Node|Variable $variableNode
     */
    public function enterNode(Node $variableNode): void
    {
        $this->validateNode($variableNode);

        if ($variableNode->name === 'this') {
            self::addTypeResolver(
                $variableNode,
                function () use ($variableNode): TypeCollection {
                    return $this->typeFactory->buildTypeCollection($variableNode);
                }
            );

            return;
        }

        $parentNode = $variableNode->getAttribute('parentNode');

        if ($parentNode instanceof Assign && $variableNode === $parentNode->var) {
            return;
        }

        if ($parentNode instanceof Param) {
            $resolver = function () use ($parentNode): TypeCollection {
                return self::resolveType($parentNode);
            };

            self::addTypeResolver($variableNode, $resolver);
            $this->variableCollector->collectResolver($this->getVariableName($variableNode), $resolver);

            return;
        }

        $this->addCollectedVariableResolver($variableNode);
    }

    public static function getNodeTypeHandled(): string
    {
        return Variable::class;
    }

    private function addCollectedVariableResolver(Variable $variableNode): void
    {
        try {
            self::addTypeResolverCollection(
                $variableNode,
                $this->variableCollector->getCollectedResolverCollection($this->getVariableName($variableNode))
            );
        } catch (UnknownVariableException $e) {
            StaticLoggerFacade::warning(
                "Silently ignoring a UnknownVariableException.\n"
                . "The variable is not in the collector, so we can't add it to the PropertyFetch.\n"
                . $e->getMessage(),
                [__METHOD__]
            );
        }
    }
}
