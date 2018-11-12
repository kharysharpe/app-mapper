<?php

declare(strict_types=1);

/*
 * This file is part of the Context Mapper application,
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

namespace Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser;

use Hgraca\ContextMapper\Core\Port\Parser\QueryBuilderInterface;
use Hgraca\ContextMapper\Core\Port\Parser\QueryInterface;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Class_;

final class QueryBuilder implements QueryBuilderInterface
{
    /** @var Query */
    private $currentQuery;

    public function create(): QueryBuilderInterface
    {
        $this->currentQuery = new Query();

        return $this;
    }

    public function selectClasses(): QueryBuilderInterface
    {
        $this->selectNodeType(Class_::class);

        return $this;
    }

    public function selectClassesExtending(string $fqcn): QueryBuilderInterface
    {
        $this->currentQuery->addFilter(
            function (Node $node) use ($fqcn) {
                // TODO recursive checking to see if it extends somewhere in the hierarchy tree
                return $node instanceof Class_
                    && $node->extends !== null
                    && $node->extends->toCodeString() === $fqcn;
            }
        );

        return $this;
    }

    public function selectClassesImplementing(string $fqcn): QueryBuilderInterface
    {
        $this->currentQuery->addFilter(
            function (Node $node) use ($fqcn) {
                // TODO recursive checking to see if it extends somewhere in the hierarchy tree
                return $node instanceof Class_
                    && $node->implements !== null
                    && $node->implements->toCodeString() === $fqcn;
            }
        );

        return $this;
    }

    public function selectClassesWithFqcnMatchingRegex(string $fqcnRegex): QueryBuilderInterface
    {
        $this->currentQuery->addFilter(
            function (Node $node) use ($fqcnRegex) {
                return $node instanceof Class_
                    && preg_match($fqcnRegex, $node->namespacedName->toCodeString());
            }
        );

        return $this;
    }

    public function selectClassWithFqcn(string $fqcn): QueryBuilderInterface
    {
        $this->currentQuery->addFilter(
            function (Node $node) use ($fqcn) {
                return $node instanceof Class_
                    && $node->namespacedName->toCodeString() === $fqcn;
            }
        );

        $this->currentQuery->returnSingleResult();

        return $this;
    }

    /**
     * TODO this filter must also be able to use the $eventDispatcherFqcn
     */
    public function selectMethodsDispatchingEvents(string $eventDispatcherMethod): QueryBuilderInterface
    {
        $this->currentQuery->addFilter(
            function (Node $node) use ($eventDispatcherMethod) {
                return $node instanceof MethodCall
                    && $node->name->name === $eventDispatcherMethod;
            }
        );

        return $this;
    }

    public function build(): QueryInterface
    {
        return $this->currentQuery;
    }

    private function selectNodeType(string $class): void
    {
        $this->currentQuery->addFilter(
            function ($node) use ($class) {
                return $node instanceof $class;
            }
        );
    }
}
