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

use Hgraca\ContextMapper\Core\Port\Parser\Exception\ParserException;
use Hgraca\ContextMapper\Core\Port\Parser\Node\MethodArgumentInterface;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Exception\ReturnTypeAstNotFoundException;
use Hgraca\PhpExtension\String\ClassService;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_;
use function is_string;

final class MethodArgumentAdapter implements MethodArgumentInterface
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
                $this->argument = $argument->getAttribute('typeAst');
                break;
            case $argument instanceof StaticCall:
                $class = new ClassAdapter($argument->class->getAttribute('ast'));
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

        if (is_string($this->argument)) {
            return $this->argument;
        }

        if ($this->argument instanceof Class_) {
            return $this->argument->namespacedName->toCodeString();
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
        if ($this->argument === null) {
            return 'unknown';
        }

        if (is_string($this->argument)) {
            return ClassService::extractCanonicalClassName($this->argument);
        }

        if ($this->argument instanceof Class_) {
            return $this->argument->namespacedName->getLast();
        }

        return $this->argument->getLast();
    }
}
