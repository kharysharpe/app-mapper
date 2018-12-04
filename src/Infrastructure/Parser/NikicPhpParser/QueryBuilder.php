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

use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Node\NodeFactory;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Visitor\AstConnectorVisitorInterface;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Class_;

final class QueryBuilder
{
    /** @var Query */
    private $currentQuery;

    public function create(): self
    {
        $this->currentQuery = new Query();

        return $this;
    }

    public function selectClasses(): self
    {
        $this->selectNodeType(Class_::class);

        return $this;
    }

    public function selectClassesExtending(string $fqcn): self
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

    public function selectClassesImplementing(string $fqcn): self
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

    public function selectClassesWithFqcnMatchingRegex(string $fqcnRegex): self
    {
        $this->currentQuery->addFilter(
            function (Node $node) use ($fqcnRegex) {
                return $node instanceof Class_
                    && preg_match($fqcnRegex, $node->namespacedName->toCodeString());
            }
        );

        return $this;
    }

    public function selectClassWithFqcn(string $fqcn): self
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

    public function selectClassesCallingMethod(
        string $eventDispatchingTypeRegex,
        string $eventDispatchingMethodRegex
    ): self {
        $this->currentQuery->addFilter(
            function (Node $node) use ($eventDispatchingTypeRegex, $eventDispatchingMethodRegex) {
                if (!$node instanceof MethodCall) {
                    return false;
                }
                $dispatcherFqcn = NodeFactory::constructTypeNodeAdapter(
                    $node->var->getAttribute(AstConnectorVisitorInterface::AST_KEY)
                )->getFullyQualifiedType();
                $dispatcherMethodName = $node->name->name;

                return $node instanceof MethodCall
                    && preg_match($eventDispatchingTypeRegex, $dispatcherFqcn)
                    && preg_match($eventDispatchingMethodRegex, $dispatcherMethodName);
            }
        );

        return $this;
    }

    public function build(): Query
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
