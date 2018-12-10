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

namespace Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Node;

use Hgraca\ContextMapper\Core\Port\Parser\Node\MethodArgumentInterface;
use Hgraca\ContextMapper\Core\Port\Parser\Node\TypeNodeInterface;
use Hgraca\ContextMapper\Core\SharedKernel\Exception\NotImplementedException;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Visitor\AbstractTypeInjectorVisitor;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Stmt\Class_;

final class MethodArgumentAdapter implements MethodArgumentInterface
{
    /**
     * @var TypeNodeInterface
     */
    private $argument;

    public function __construct(Expr $argument)
    {
        switch (true) {
            case $argument instanceof New_:
                $this->argument = NodeFactory::constructTypeNodeAdapter(
                    AbstractTypeInjectorVisitor::getTypeFromNode($argument)
                );
                break;
            case $argument instanceof Variable:
                $this->argument = NodeFactory::constructTypeNodeAdapter(
                    AbstractTypeInjectorVisitor::getTypeFromNode($argument)
                );
                break;
            case $argument instanceof StaticCall && $this->staticCallClassAstIsKnown($argument):
                $class = ClassAdapter::constructFromClassNode($this->getStaticCallClassAst($argument));
                $method = $class->getMethod($argument->name->toString());
                $this->argument = $method->getReturnTypeNode();
                break;
            default:
                $this->argument = new UnknownTypeNode($argument);
        }
    }

    public function getArgumentNode(): TypeNodeInterface
    {
        return $this->argument;
    }

    public function getFullyQualifiedType(): string
    {
        return $this->argument->getFullyQualifiedType();
    }

    public function getCanonicalType(): string
    {
        return $this->argument->getCanonicalType();
    }

    public function getAllFamilyFullyQualifiedNameList(): array
    {
        throw new NotImplementedException();
    }

    private function staticCallClassAstIsKnown(StaticCall $argument): bool
    {
        return AbstractTypeInjectorVisitor::getTypeFromNode($argument->class)->hasAst();
    }

    private function getStaticCallClassAst(StaticCall $argument): Class_
    {
        /** @var Class_ $class */
        $class = AbstractTypeInjectorVisitor::getTypeFromNode($argument->class)->getAst();

        return $class;
    }
}
