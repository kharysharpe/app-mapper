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

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;

final class MethodCallAdapter
{
    /**
     * @var MethodCall
     */
    private $methodCall;

    /**
     * @var ClassAdapter
     */
    private $enclosingClass;

    /**
     * @var MethodAdapter
     */
    private $enclosingMethod;

    /**
     * @var ArgumentAdapter[]
     */
    private $argumentList = [];

    public function __construct(MethodCall $methodCall)
    {
        $this->methodCall = $methodCall;
        /** @var Arg $argument */
        foreach ($methodCall->args as $argument) {
            $this->argumentList[] = new ArgumentAdapter($argument->value);
        }
    }

    public function getEnclosingClassFullyQualifiedName(): string
    {
        return $this->getEnclosingClass()->getFullyQualifiedClassName();
    }

    public function getEnclosingClassCanonicalName(): string
    {
        return $this->getEnclosingClass()->getCanonicalClassName();
    }

    public function getEnclosingMethodCanonicalName(): string
    {
        return $this->getEnclosingMethod()->getCanonicalName();
    }

    public function getArgumentFullyQualifiedType(int $argumentIndex = 0): string
    {
        return $this->argumentList[$argumentIndex]->getFullyQualifiedType();
    }

    public function getArgumentCanonicalType(int $argumentIndex = 0): string
    {
        return $this->argumentList[$argumentIndex]->getCanonicalType();
    }

    private function getEnclosingClass(): ClassAdapter
    {
        if ($this->enclosingClass === null) {
            $node = $this->methodCall;
            do {
                $node = $node->getAttribute('parent');
            } while (!$node instanceof Class_);

            $this->enclosingClass = new ClassAdapter($node);
        }

        return $this->enclosingClass;
    }

    private function getEnclosingMethod(): MethodAdapter
    {
        if ($this->enclosingMethod === null) {
            $node = $this->methodCall;
            do {
                $node = $node->getAttribute('parent');
            } while (!$node instanceof ClassMethod);

            $this->enclosingMethod = new MethodAdapter($node);
        }

        return $this->enclosingMethod;
    }
}
