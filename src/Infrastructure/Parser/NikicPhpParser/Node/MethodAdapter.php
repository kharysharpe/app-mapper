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

use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Exception\ReturnTypeAstNotFoundException;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Exception\ReturnTypeNameNotFoundException;
use PhpParser\Node;
use PhpParser\Node\Identifier;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Trait_;

final class MethodAdapter
{
    /**
     * @var ClassMethod
     */
    private $classMethod;

    public function __construct(ClassMethod $classMethod)
    {
        $this->classMethod = $classMethod;
    }

    public function getCanonicalName(): string
    {
        return $this->classMethod->name->toString();
    }

    /**
     * @return Class_|Interface_|Trait_
     */
    public function getReturnTypeAst(): Node
    {
        $returnType = $this->classMethod->getReturnType();

        if ($returnType !== null && $returnType->hasAttribute('ast')) {
            return $returnType->getAttribute('ast');
        }

        throw new ReturnTypeAstNotFoundException();
    }

    public function getReturnType(): string
    {
        $returnType = $this->classMethod->getReturnType();

        if ($returnType->hasAttribute('resolvedName')) {
            return (string) $returnType->getAttribute('resolvedName');
        }

        if ($returnType instanceof Identifier) {
            return $returnType->name;
        }

        throw new ReturnTypeNameNotFoundException();
    }
}
