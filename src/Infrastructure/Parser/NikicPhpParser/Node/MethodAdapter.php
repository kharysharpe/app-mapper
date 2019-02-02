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

use Hgraca\AppMapper\Core\Port\Parser\Node\AdapterNodeCollection;
use Hgraca\AppMapper\Core\Port\Parser\Node\MethodInterface;
use Hgraca\AppMapper\Core\Port\Parser\Node\MethodParameterInterface;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeDecoratorAccessorTrait;
use PhpParser\Node\Stmt\ClassMethod;

final class MethodAdapter implements MethodInterface
{
    use NodeDecoratorAccessorTrait;

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

    public function getReturnTypeCollection(): AdapterNodeCollection
    {
        $returnTypeDecorator = $this->getNodeDecorator($this->classMethod->getReturnType());

        return NodeAdapterFactory::constructFromTypeCollection($returnTypeDecorator->getTypeCollection());
    }

    public function getParameter(int $index): MethodParameterInterface
    {
        return new MethodParameterAdapter($this->classMethod->params[$index]);
    }

    public function isConstructor(): bool
    {
        return $this->getCanonicalName() === '__construct';
    }

    public function isPublic(): bool
    {
        return $this->classMethod->isPublic();
    }
}
