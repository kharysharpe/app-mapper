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
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\ClassMethodNodeDecorator;

final class MethodAdapter implements MethodInterface
{
    use NodeDecoratorAccessorTrait;

    /**
     * @var ClassMethodNodeDecorator
     */
    private $classMethod;

    /**
     * @var NodeAdapterFactory
     */
    private $nodeAdapterFactory;

    public function __construct(ClassMethodNodeDecorator $classMethod)
    {
        $this->classMethod = $classMethod;
        $this->nodeAdapterFactory = new NodeAdapterFactory();
    }

    public function getCanonicalName(): string
    {
        return $this->classMethod->getName();
    }

    public function getReturnTypeCollection(): AdapterNodeCollection
    {
        return $this->nodeAdapterFactory->constructFromTypeCollection($this->classMethod->getReturnTypeCollection());
    }

    public function getParameter(int $index): MethodParameterInterface
    {
        return new MethodParameterAdapter($this->classMethod->getParameter($index));
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
