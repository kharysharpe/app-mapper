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

namespace Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Node;

use Hgraca\AppMapper\Core\Port\Parser\Node\ClassInterface;
use Hgraca\AppMapper\Core\Port\Parser\Node\MethodArgumentInterface;
use Hgraca\AppMapper\Core\Port\Parser\Node\MethodCallInterface;
use Hgraca\AppMapper\Core\Port\Parser\Node\MethodInterface;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeDecoratorAccessorTrait;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\MethodCallNodeDecorator;
use PhpParser\Node\Arg;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;

final class MethodCallAdapter implements MethodCallInterface
{
    use NodeDecoratorAccessorTrait;

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
            $this->argumentList[] = new MethodArgumentAdapter($argument);
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

    public function getLine(): int
    {
        return (int) $this->methodCall->getAttribute('startLine');
    }

    private function getEnclosingClass(): ClassInterface
    {
        if ($this->enclosingClass === null) {
            /** @var MethodCallNodeDecorator $methodCallDecorator */
            $methodCallDecorator = $this->getNodeDecorator($this->methodCall);
            /** @var Class_ $node */
            $node = $methodCallDecorator->getEnclosingClassNode()->getInnerNode();
            $this->enclosingClass = ClassAdapter::constructFromClassNode($node);
        }

        return $this->enclosingClass;
    }

    private function getEnclosingMethod(): MethodInterface
    {
        if ($this->enclosingMethod === null) {
            /** @var MethodCallNodeDecorator $methodCallDecorator */
            $methodCallDecorator = $this->getNodeDecorator($this->methodCall);
            /** @var ClassMethod $node */
            $node = $methodCallDecorator->getEnclosingMethodNode()->getInnerNode();
            $this->enclosingMethod = new MethodAdapter($node);
        }

        return $this->enclosingMethod;
    }
}
