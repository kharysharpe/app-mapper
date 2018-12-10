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

namespace Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Visitor;

use PhpParser\Node;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\ClassMethod;

final class MethodParametersTypeInjectorVisitor extends AbstractTypeInjectorVisitor
{
    use VariableBufferTrait;

    public function enterNode(Node $node): void
    {
        parent::enterNode($node);
        switch (true) {
            case $node instanceof ClassMethod:
                // Parameters in the method signature
                $this->addTypeToMethodParameters(...$node->params);
                break;
            case $node instanceof Variable:
                // Parameters used within the method
                if ($this->hasVariableTypeInBuffer((string) $node->name)) {
                    $this->addTypeToNode(
                        $node,
                        $this->getVariableTypeFromBuffer((string) $node->name)
                    );
                }
                break;
        }
    }

    public function leaveNode(Node $node): void
    {
        if ($node instanceof ClassMethod) {
            $this->resetVariableTypeBuffer();
        }
    }

    private function addTypeToMethodParameters(Param ...$methodParameterList): void
    {
        foreach ($methodParameterList as $methodParameter) {
            $this->addTypeToNode($methodParameter, $this->buildType($methodParameter));
            $this->addVariableTypeToBuffer($methodParameter->var->name, self::getTypeFromNode($methodParameter));
        }
    }
}
