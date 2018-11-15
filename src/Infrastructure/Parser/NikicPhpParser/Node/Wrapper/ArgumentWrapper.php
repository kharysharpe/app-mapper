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

namespace Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Node\Wrapper;

use Hgraca\ContextMapper\Core\Port\Parser\Exception\ParserException;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Exception\ReturnTypeAstNotFoundException;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;

final class ArgumentWrapper
{
    /**
     * @var Name
     */
    private $argument;

    public function __construct(Expr $argument)
    {
        switch (true) {
            case $argument instanceof New_:
                $this->argument = $argument->class;
                break;
            case $argument instanceof Variable:
//                $this->argument = ; // TODO infer type from inspecting variable creation
                break;
            case $argument instanceof StaticCall:
                $class = new ClassWrapper($argument->class->getAttribute('ast'));
                $method = $class->getMethod($argument->name->toString());
                try {
                    $this->argument = $method->getReturnTypeAst()->namespacedName;
                } catch (ReturnTypeAstNotFoundException $e) {
                    // We silently ignore this exception so we continue the operation and in the end write 'unknown'
                }
                break;
            default:
                throw new ParserException("Can't get the argument node.");
        }
    }

    public function getFullyQualifiedType(): string
    {
        if ($this->argument === null) {
            return 'unknown';
        }

        /** @var FullyQualified $argumentName */
        $argumentName = $this->argument->getAttribute('resolvedName');
        if ($argumentName === null) {
            /** @var Name $argumentName */
            $argumentName = $this->argument;
        }

        return $argumentName->toCodeString();
    }

    public function getCanonicalType(): string
    {
        return ($this->argument === null)
            ? 'unknown'
            : $this->argument->getLast();
    }
}
