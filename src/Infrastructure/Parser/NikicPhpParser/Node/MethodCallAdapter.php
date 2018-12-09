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

use Hgraca\ContextMapper\Core\Port\Parser\Node\ClassInterface;
use Hgraca\ContextMapper\Core\Port\Parser\Node\MethodArgumentInterface;
use Hgraca\ContextMapper\Core\Port\Parser\Node\MethodCallInterface;
use Hgraca\ContextMapper\Core\Port\Parser\Node\MethodInterface;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Visitor\ParentConnectorVisitor;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;

final class MethodCallAdapter implements MethodCallInterface
{
    /**
     * @var MethodCall
     */
    private $methodCall;

    /**
     * @var ClassInterface
     */
    private $enclosingClass;

    /**
     * @var MethodInterface
     */
    private $enclosingMethod;

    /**
     * @var MethodArgumentInterface[]
     */
    private $argumentList = [];

    public function __construct(MethodCall $methodCall)
    {
        $this->methodCall = $methodCall;
        /** @var Arg $argument */
        foreach ($methodCall->args as $argument) {
            $this->argumentList[] = new MethodArgumentAdapter($argument->value);
        }
    }

    public function getEnclosingClassFullyQualifiedName(): string
    {
        return $this->getEnclosingClass()->getFullyQualifiedType();
    }

    public function getEnclosingClassCanonicalName(): string
    {
        return $this->getEnclosingClass()->getCanonicalType();
    }

    public function getEnclosingMethodCanonicalName(): string
    {
        return $this->getEnclosingMethod()->getCanonicalName();
    }

    public function getMethodArgument(int $argumentIndex = 0): MethodArgumentInterface
    {
        return $this->argumentList[$argumentIndex];
    }

    public function getArgumentFullyQualifiedType(int $argumentIndex = 0): string
    {
        return $this->argumentList[$argumentIndex]->getFullyQualifiedType();
    }

    public function getArgumentCanonicalType(int $argumentIndex = 0): string
    {
        return $this->argumentList[$argumentIndex]->getCanonicalType();
    }

    public function getLine(): int
    {
        return (int) $this->methodCall->getAttribute('startLine');
    }

    private function getEnclosingClass(): ClassInterface
    {
        if ($this->enclosingClass === null) {
            $node = $this->methodCall;
            do {
                $node = $node->getAttribute(ParentConnectorVisitor::PARENT_NODE);
            } while (!$node instanceof Class_);

            $this->enclosingClass = new ClassAdapter($node);
        }

        return $this->enclosingClass;
    }

    private function getEnclosingMethod(): MethodInterface
    {
        if ($this->enclosingMethod === null) {
            $node = $this->methodCall;
            do {
                $node = $node->getAttribute(ParentConnectorVisitor::PARENT_NODE);
            } while (!$node instanceof ClassMethod);

            $this->enclosingMethod = new MethodAdapter($node);
        }

        return $this->enclosingMethod;
    }
}
