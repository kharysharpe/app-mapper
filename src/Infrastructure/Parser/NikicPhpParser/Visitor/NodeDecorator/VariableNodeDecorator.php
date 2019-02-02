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

use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Type;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeCollection;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Foreach_;

/**
 * @property Variable $node
 */
final class VariableNodeDecorator extends AbstractNodeDecorator implements NamedNodeDecoratorInterface
{
    use AssignableNodeTrait;

    public function __construct(Variable $node, AbstractNodeDecorator $parentNode)
    {
        parent::__construct($node, $parentNode);
    }

    public function resolveTypeCollection(): TypeCollection
    {
        if ($this->isSelf()) {
            return $this->getSelfTypeCollection();
        }

        if ($this->isParameterDeclaration()) {
            /* @noinspection NullPointerExceptionInspection */
            return $this->getParentNode()->getTypeCollection();
        }

        if ($this->isAssignee()) {
            /** @var AssignNodeDecorator $parentNode */
            $parentNode = $this->getParentNode();

            return $parentNode->getExpression()->getTypeCollection();
        }

        if ($this->isKeyInForeach()) {
            return new TypeCollection(new Type('string'), new Type('int'));
        }

        if ($this->isValueInForeach()) {
            return $this->getAllNestedTypesInForeachExpression();
        }

        return $this->getSiblingTypeCollection();
    }

    public function getName(): string
    {
        return (string) $this->node->name;
    }

    public function isParameterDeclaration(): bool
    {
        return $this->getParentNode() instanceof ParamNodeDecorator;
    }

    private function getAllNestedTypesInForeachExpression(): TypeCollection
    {
        /** @var Foreach_ $foreachNode */
        $foreachNode = $this->getParentNode()->node;
        $expressionDecorator = $this->getNodeDecorator($foreachNode->expr);

        /** @var Type[] $typeCollection */
        $typeCollection = $expressionDecorator->getTypeCollection();

        $nestedTypeCollection = new TypeCollection();
        foreach ($typeCollection as $type) {
            if ($type->hasNestedType()) {
                $nestedTypeCollection = $nestedTypeCollection->addType($type->getNestedType());
            }
        }

        return $nestedTypeCollection;
    }

    private function isSelf(): bool
    {
        return $this->getName() === 'this';
    }
}
