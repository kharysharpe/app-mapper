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
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Type;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeCollection;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeResolverCollector;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Foreach_;

final class ForeachNodeStrategy extends AbstractStrategy
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
     * @param Node|Foreach_ $foreachNode
     */
    public function enterNode(Node $foreachNode): void
    {
        $this->validateNode($foreachNode);

        $this->assignTypeToForeachKey($foreachNode->keyVar);
        $this->assignTypeToForeachVar($foreachNode->expr, $foreachNode->valueVar);
    }

    public static function getNodeTypeHandled(): string
    {
        return Foreach_::class;
    }

    private function assignTypeToForeachKey(?Expr $keyVar): void
    {
        if ($keyVar === null) {
            return;
        }

        $resolver = function (): TypeCollection {
            return new TypeCollection(new Type('string'), new Type('int'));
        };

        self::addTypeResolver($keyVar, $resolver);
        if ($keyVar instanceof Variable) {
            // Assignment to variable
            $this->variableCollector->resetResolverCollection($this->getVariableName($keyVar), $resolver);
        } elseif ($keyVar instanceof PropertyFetch) {
            // Assignment to property
            $this->propertyCollector->collectResolver($this->getPropertyName($keyVar), $resolver);
        }
    }

    private function assignTypeToForeachVar(Expr $expression, Expr $valueVar): void
    {
        $resolver = function () use ($expression): TypeCollection {
            try {
                /** @var Type[] $typeCollection */
                $typeCollection = self::resolveType($expression);

                $nestedTypeCollection = new TypeCollection();
                foreach ($typeCollection as $type) {
                    if ($type->hasNestedType()) {
                        $nestedTypeCollection = $nestedTypeCollection->addType($type->getNestedType());
                    }
                }

                return $nestedTypeCollection;
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

        self::addTypeResolver($valueVar, $resolver);
        if ($valueVar instanceof Variable) {
            // Assignment to variable
            $this->variableCollector->resetResolverCollection($this->getVariableName($valueVar), $resolver);
        } elseif ($valueVar instanceof PropertyFetch) {
            // Assignment to property
            $this->propertyCollector->collectResolver($this->getPropertyName($valueVar), $resolver);
        }
    }
}
