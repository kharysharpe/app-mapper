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
use Hgraca\AppMapper\Core\Port\Parser\Node\ClassInterface;

final class UseCaseNode implements DomainNodeInterface
{
    /** @var string */
    private $fqcn;

    /** @var string */
    private $canonicalClassName;

    /**
     * @var Component|null
     */
    private $component;

    private function __construct()
    {
    }

    /**
     * @return static
     */
    public static function constructFromNode(ClassInterface $class)
    {
        $self = new self();

        $self->fqcn = $class->getFullyQualifiedType();
        $self->canonicalClassName = $class->getCanonicalType();

        return $self;
    }

    public function getFullyQualifiedName(): string
    {
        return $this->fqcn;
    }

    public function getCanonicalName(): string
    {
        return $this->canonicalClassName;
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
