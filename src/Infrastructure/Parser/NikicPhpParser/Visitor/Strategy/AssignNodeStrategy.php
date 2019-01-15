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
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\UnresolvableNodeTypeException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeTypeManagerTrait;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeCollection;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeResolverCollector;
use PhpParser\Node;
use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;

final class AssignNodeStrategy extends AbstractStrategy
{
    use NodeTypeManagerTrait;
    use VariableNameExtractorTrait;

    private $propertyCollector;

    private $variableCollector;

    public function __construct(TypeResolverCollector $propertyCollector, TypeResolverCollector $variableCollector)
    {
        $this->propertyCollector = $propertyCollector;
        $this->variableCollector = $variableCollector;
    }

    /**
     * @param Node|Assign $assignNode
     */
    public function leaveNode(Node $assignNode): void
    {
        $this->validateNode($assignNode);

        $variable = $assignNode->var;
        $expression = $assignNode->expr;

        $resolver = function () use ($expression): TypeCollection {
            try {
                return self::resolveType($expression);
            } catch (UnresolvableNodeTypeException $e) {
                StaticLoggerFacade::warning(
                    "Silently ignoring a UnresolvableNodeTypeException in this filter.\n"
                    . 'This is failing, at least, for nested method calls like'
                    . '`$invoice->transactions->first()->getServicePro();`.' . "\n"
                    . "This should be fixed in the type addition visitors.\n"
                    . $e->getMessage(),
                    [__METHOD__]
                );

                return new TypeCollection();
            }
        };

        self::addTypeResolver($variable, $resolver);
        if ($variable instanceof Variable) {
            // Assignment to variable
            $this->variableCollector->resetResolverCollection($this->getVariableName($variable), $resolver);
        } elseif ($variable instanceof PropertyFetch) {
            // Assignment to property
            $this->propertyCollector->collectResolver($this->getPropertyName($variable), $resolver);
        }
    }

    public static function getNodeTypeHandled(): string
    {
        return Assign::class;
    }
}
