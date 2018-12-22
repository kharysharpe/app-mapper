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
use Hgraca\AppMapper\Core\Port\Logger\StaticLoggerFacade;
use Hgraca\AppMapper\Core\Port\Parser\Node\ClassInterface;
use Hgraca\AppMapper\Core\Port\Parser\Node\MethodInterface;
use Hgraca\PhpExtension\String\ClassHelper;

final class ListenerNode implements DomainNodeInterface
{
    /** @var string */
    private $fqcn;

    /** @var string */
    private $canonicalClassName;

    /** @var string */
    private $methodName;

    /** @var string */
    private $event;

    /** @var string */
    private $eventFqcn;

    /**
     * @var Component|null
     */
    private $component;

    private function __construct()
    {
    }

    public static function constructFromClassAndMethod(ClassInterface $class, MethodInterface $method): self
    {
        $self = new self();
        $self->fqcn = $class->getFullyQualifiedType();
        $self->canonicalClassName = $class->getCanonicalType();
        $self->methodName = $method->getCanonicalName();
        StaticLoggerFacade::notice(
            'TODO Currently we assume events are always the first argument. '
            . 'But this will need to be improved to accommodate projects that don\'t follow this coding standard',
            [__METHOD__]
        );
        $self->event = $method->getParameter(0)->getCanonicalType();
        $self->eventFqcn = $method->getParameter(0)->getFullyQualifiedType();

        return $self;
    }

    public function getFullyQualifiedName(): string
    {
        return $this->fqcn . '::' . $this->methodName;
    }

    public function getCanonicalName(): string
    {
        return ClassHelper::extractCanonicalClassName($this->fqcn) . '::' . $this->methodName;
    }

    public function listensTo(EventDispatcherNode $eventDispatcher): bool
    {
        return $eventDispatcher->dispatches($this->eventFqcn);
    }

    public function getListenedFqcn(): string
    {
        return $this->eventFqcn;
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
