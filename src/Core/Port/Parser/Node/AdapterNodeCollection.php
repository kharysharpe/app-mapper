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

namespace Hgraca\AppMapper\Core\Port\Parser\Node;

use Hgraca\AppMapper\Core\Component\Main\Domain\DomainNodeCollection;
use Hgraca\AppMapper\Core\Component\Main\Domain\Node\EventDispatcherNode;
use Hgraca\AppMapper\Core\Component\Main\Domain\Node\MethodCallerNode;
use Hgraca\AppMapper\Core\Component\Main\Domain\Node\UseCaseNode;
use Hgraca\PhpExtension\Collection\Collection;

final class AdapterNodeCollection extends Collection
{
    public function __construct(AdapterNodeInterface ...$itemList)
    {
        parent::__construct($itemList);
    }

    /**
     * @param string|UseCaseNode|EventDispatcherNode|MethodCallerNode $fqcn
     *
     * @return DomainNodeCollection
     */
    public function decorateByDomainNode(string $fqcn): DomainNodeCollection
    {
        $domainNodeList = [];
        foreach ($this->itemList as $nodeAdapter) {
            // FIXME this will break if the class does not have a `constructFromNode` method, we should use a factory
            $domainNodeList[] = $fqcn::constructFromNode($nodeAdapter);
        }

        return new DomainNodeCollection(...$domainNodeList);
    }

    /**
     * @return AdapterNodeInterface[]
     */
    public function toArray(): array
    {
        return $this->itemList;
    }
}
