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

use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Exception\MethodNotFoundInClassException;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;

final class ClassWrapper
{
    /**
     * @var Class_
     */
    private $class;

    public function __construct(Class_ $class)
    {
        $this->class = $class;
    }

    public function getFullyQualifiedClassName(): string
    {
        return $this->class->namespacedName->toCodeString();
    }

    public function getCanonicalClassName(): string
    {
        return $this->class->name->toString();
    }

    public function getMethod(string $methodName): MethodWrapper
    {
        foreach ($this->class->stmts as $stmt) {
            if (
                $stmt instanceof ClassMethod
                && $stmt->name->toString() === $methodName
            ) {
                return new MethodWrapper($stmt);
            }
        }

        throw new MethodNotFoundInClassException($methodName, $this->getFullyQualifiedClassName());
    }
}
