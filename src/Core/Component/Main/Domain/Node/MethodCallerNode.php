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

namespace Hgraca\AppMapper\Core\Component\Main\Domain\Node;

use Hgraca\AppMapper\Core\Component\Main\Domain\Component;
use Hgraca\AppMapper\Core\Port\Parser\Node\MethodCallInterface;

class MethodCallerNode implements DomainNodeInterface
{
    /** @var string */
    protected $dispatcherClassCanonicalName;

    /** @var string */
    protected $dispatcherClassFqcn;

    /** @var string */
    protected $dispatcherMethod;

    /**
     * @var Component|null
     */
    protected $component;

    /**
     * @var int
     */
    protected $methodCallLine;

    protected function __construct()
    {
    }

    /**
     * @return static
     */
    public static function constructFromNode(MethodCallInterface $methodCall)
    {
        $self = new self();

        $self->dispatcherClassCanonicalName = $methodCall->getEnclosingClassCanonicalName();
        $self->dispatcherClassFqcn = $methodCall->getEnclosingClassFullyQualifiedName();
        $self->dispatcherMethod = $methodCall->getEnclosingMethodCanonicalName();
        $self->methodCallLine = $methodCall->getLine();

        return $self;
    }

    public function getFullyQualifiedName(): string
    {
        return $this->dispatcherClassFqcn . '::' . $this->getDispatcherMethod() . '::' . $this->methodCallLine;
    }

    public function getCanonicalName(): string
    {
        return $this->dispatcherClassCanonicalName . '::' . $this->getDispatcherMethod();
    }

    public function getDispatcherClassFqcn(): string
    {
        return $this->dispatcherClassFqcn;
    }

    public function getDispatcherMethod(): string
    {
        return $this->dispatcherMethod;
    }

    public function setComponent(Component $component): void
    {
        $this->component = $component;
    }

    public function getComponent(): ?Component
    {
        return $this->component;
    }
}
