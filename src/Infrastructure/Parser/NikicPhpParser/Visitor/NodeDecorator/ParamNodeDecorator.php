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

namespace Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator;

use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeCollection;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeFinder;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeCollection;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Param;
use function array_values;

/**
 * @property Param $node
 */
final class ParamNodeDecorator extends AbstractNodeDecorator implements NamedNodeDecoratorInterface
{
    /**
     * @var NodeFinder
     */
    private $nodeFinder;

    public function __construct(Param $node, AbstractNodeDecorator $parentNode, NodeCollection $nodeCollection)
    {
        parent::__construct($node, $parentNode);
        $this->nodeCollection = $nodeCollection;
        $this->nodeFinder = new NodeFinder();
    }

    public function resolveTypeCollection(): TypeCollection
    {
        $typeCollection = $this->getDeclaredType()
            ->getTypeCollection()
            ->addTypeCollection(
                $this->getDefaultValueTypeCollection()
            );

        if ($this->isWithinClass() && !$typeCollection->isConcrete() && !$this->isParameterOfClosure()) {
            // FIXME this makes it VERY slow! We need to investigate why and maybe make it optional using a CLI param.
            $this->addTypesFromMethodCallsArgument();
        }

        return $typeCollection->addTypeCollection($this->getSiblingTypeCollection());
    }

    public function getDeclaredType(): AbstractNodeDecorator
    {
        return $this->node->type === null
            ? new NullNodeDecorator($this)
            : $this->getNodeDecorator($this->node->type);
    }

    public function getName(): string
    {
        return (string) $this->node->var->name;
    }

    private function getDefaultValueTypeCollection(): TypeCollection
    {
        return $this->node->default
            ? $this->getNodeDecorator($this->node->default)->getTypeCollection()
            : new TypeCollection();
    }

    /**
     * @return MethodCallNodeDecorator[]
     */
    private function findMethodCalls(string $dispatcherTypeRegex, string $methodRegex): array
    {
        $nodeList = $this->nodeFinder->find(
            $this->getFindMethodCallsFilter($dispatcherTypeRegex, $methodRegex),
            ...array_values($this->nodeCollection->toArray())
        );

        $decoratorList = [];
        foreach ($nodeList as $node) {
            $decoratorList[] = $this->getNodeDecorator($node);
        }

        return $decoratorList;
    }

    private function getFindMethodCallsFilter(string $dispatcherTypeRegex, string $methodRegex): callable
    {
        return function (Node $node) use ($dispatcherTypeRegex, $methodRegex) {
            if (!$node instanceof MethodCall) {
                return false;
            }
            /** @var MethodCallNodeDecorator $methodCallDecorator */
            $methodCallDecorator = $this->getNodeDecorator($node);
            $calleeTypeCollection = $methodCallDecorator->getCallee()->getTypeCollection();

            foreach ($calleeTypeCollection as $type) {
                if (
                    $methodRegex === $methodCallDecorator->getMethodName()
                    && $dispatcherTypeRegex === $type->getFqn()
                ) {
                    return true;
                }
            }

            return false;
        };
    }

    private function getParamIndex(): int
    {
        return $this->getClassMethod()->getParameterIndex($this);
    }

    private function getClassMethod(): ClassMethodNodeDecorator
    {
        return $this->getFirstParentNodeOfType(ClassMethodNodeDecorator::class);
    }

    private function isWithinClass(): bool
    {
        return $this->getEnclosingInterfaceLikeNode() instanceof StmtClassNodeDecorator;
    }

    private function isParameterOfClosure(): bool
    {
        return $this->getParentNode() instanceof ClosureNodeDecorator;
    }

    /**
     * @param TypeCollection $typeCollection
     *
     * @return TypeCollection
     */
    private function addTypesFromMethodCallsArgument(): void
    {
        $methodCallList = $this->findMethodCalls(
            $this->getEnclosingClassLikeNode()->getTypeCollection()->getUniqueType()->getFqn(),
            $this->getEnclosingMethodNode()->getName()
        );

        $paramIndex = $this->getParamIndex();

        $argumentList = [];
        foreach ($methodCallList as $methodCall) {
            $argument = $methodCall->getArgumentInIndex($paramIndex);
            if ($argument) {
                $argumentList[] = $argument;
            }
        }

        if (!empty($argumentList)) {
            $this->addSiblingNodes(...$argumentList);
        }
    }
}
