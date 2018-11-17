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

namespace Hgraca\ContextMapper\Core\Component\Main\Domain;

use Hgraca\ContextMapper\Core\Port\Parser\Node\ClassInterface;

final class ListenerNode implements DomainNodeInterface
{
    /** @var string */
    private $fqcn;

    /** @var string */
    private $canonicalClassName;

    /** @var string[][] */
    private $methodList = [];

    public function __construct(ClassInterface $class)
    {
        $this->fqcn = $class->getFullyQualifiedType();
        $this->canonicalClassName = $class->getCanonicalType();
        foreach ($class->getMethodList() as $key => $method) {
            if ($method->isConstructor() || !$method->isPublic()) {
                continue;
            }
            $this->methodList[$key]['name'] = $method->getCanonicalName();
            // TODO we assume the event is always the 1st parameter,
            // but should actually search for the first parameter that is an event
            $this->methodList[$key]['event'] = $method->getParameter(0)->getCanonicalType();
            $this->methodList[$key]['eventFqcn'] = $method->getParameter(0)->getFullyQualifiedType();
        }
    }

    public function toArray(): array
    {
        return [
            'Listener' => $this->canonicalClassName,
            'Methods' => $this->methodList,
            'Listener FQCN' => $this->fqcn,
        ];
    }
}
