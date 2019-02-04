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
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\ArgNodeDecorator;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\MethodCallNodeDecorator;

final class MethodCallAdapter implements MethodCallInterface
{
    use NodeDecoratorAccessorTrait;

    /**
     * @var MethodCallNodeDecorator
     */
    private $methodCallNodeDecorator;

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

    public function __construct(MethodCallNodeDecorator $methodCallNodeDecorator)
    {
        $this->methodCallNodeDecorator = $methodCallNodeDecorator;
        /** @var ArgNodeDecorator $argumentDecorator */
        foreach ($methodCallNodeDecorator->getArguments() as $argumentDecorator) {
            $this->argumentList[] = new MethodArgumentAdapter($argumentDecorator);
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
        return $this->methodCallNodeDecorator->getLine();
    }

    private function getEnclosingClass(): ClassInterface
    {
        if ($this->enclosingClass === null) {
            $this->enclosingClass = ClassAdapter::constructFromClassNode(
                $this->methodCallNodeDecorator->getEnclosingClassLikeNode()
            );
        }

        return $this->enclosingClass;
    }

    private function getEnclosingMethod(): MethodInterface
    {
        if ($this->enclosingMethod === null) {
            $this->enclosingMethod = new MethodAdapter($this->methodCallNodeDecorator->getEnclosingMethodNode());
        }

        return $this->enclosingMethod;
    }
}
