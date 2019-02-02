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

namespace Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser;

use Hgraca\AppMapper\Core\Port\Logger\StaticLoggerFacade;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\MethodCallNodeDecorator;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Class_;

final class QueryBuilder
{
    use NodeDecoratorAccessorTrait;

    /** @var Query */
    private $currentQuery;

    public function create(): self
    {
        $this->currentQuery = new Query();

        return $this;
    }

    public function selectComponent(string $componentName): self
    {
        $this->currentQuery->addComponentFilter($componentName);

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
                StaticLoggerFacade::notice(
                    'TODO Currently we only select classes that extend the given class directly. '
                    . 'We need to implement recursive checking to see if it extends somewhere in the hierarchy tree',
                    [__METHOD__]
                );

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
                StaticLoggerFacade::notice(
                    'TODO Currently we only select classes that implement the given class directly. '
                    . 'We need to implement recursive checking to see if it implements somewhere in the hierarchy tree',
                    [__METHOD__]
                );

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
        string $eventDispatcherTypeRegex,
        string $eventDispatcherMethodRegex
    ): self {
        $this->currentQuery->addFilter(
            function (Node $node) use ($eventDispatcherTypeRegex, $eventDispatcherMethodRegex) {
                if (!$node instanceof MethodCall) {
                    return false;
                }
                /** @var MethodCallNodeDecorator $methodCallDecorator */
                $methodCallDecorator = $this->getNodeDecorator($node);
                $calleeTypeCollection = $methodCallDecorator->getCallee()->getTypeCollection();

                foreach ($calleeTypeCollection as $type) {
                    if (
                        preg_match($eventDispatcherMethodRegex, $methodCallDecorator->getMethodName())
                        && preg_match($eventDispatcherTypeRegex, $type->getFqn())
                    ) {
                        return true;
                    }
                }

                return false;
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
