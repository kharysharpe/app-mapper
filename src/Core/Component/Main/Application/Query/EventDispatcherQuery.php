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

namespace Hgraca\ContextMapper\Core\Component\Main\Application\Query;

use Hgraca\ContextMapper\Core\Component\Main\Domain\DomainNodeCollection;
use Hgraca\ContextMapper\Core\Component\Main\Domain\EventDispatcherNode;
use Hgraca\ContextMapper\Core\Port\Parser\AstMapInterface;
use Hgraca\ContextMapper\Core\Port\Parser\QueryBuilderInterface;

final class EventDispatcherQuery
{
    /**
     * @var QueryBuilderInterface
     */
    private $queryBuilder;

    public function __construct(QueryBuilderInterface $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    public function queryAst(AstMapInterface $ast): DomainNodeCollection
    {
        $query = $this->queryBuilder->create()
            ->selectMethodsDispatchingEvents(
                '\Werkspot\Instapro\Infrastructure\EventDispatcher\EventDispatcherInterface',
                'dispatch'
            )
            ->build();

        return $ast->query($query)->decorateByDomainNode(EventDispatcherNode::class);
    }
}
