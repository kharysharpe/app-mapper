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

namespace Hgraca\ContextMapper\Core\Component\Main\Domain\Node;

use Hgraca\ContextMapper\Core\Component\Main\Domain\Component;
use Hgraca\PhpExtension\String\ClassService;

final class PartialUseCaseNode implements DomainNodeInterface
{
    /** @var string */
    private $fullyQualifiedClassName;

    /** @var string */
    private $method;

    /** @var Component */
    private $component;

    public static function constructFromEventDispatcher(EventDispatcherNode $eventDispatcher): self
    {
        $self = new self();

        $self->fullyQualifiedClassName = $eventDispatcher->getDispatcherClassFqcn();
        $self->method = $eventDispatcher->getDispatcherMethod();
        $self->component = $eventDispatcher->getComponent();

        return $self;
    }

    public function getComponent(): Component
    {
        return $this->component;
    }

    public function getFullyQualifiedName(): string
    {
        return $this->fullyQualifiedClassName . '::' . $this->method;
    }

    public function getCanonicalName(): string
    {
        return ClassService::extractCanonicalClassName($this->fullyQualifiedClassName) . '::' . $this->method;
    }
}
