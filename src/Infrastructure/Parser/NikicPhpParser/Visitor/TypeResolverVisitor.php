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

namespace Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor;

use Hgraca\AppMapper\Core\Port\Logger\StaticLoggerFacade;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeTypeManagerTrait;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

final class TypeResolverVisitor extends NodeVisitorAbstract
{
    use NodeTypeManagerTrait;

    public function enterNode(Node $node): void
    {
        if (!$node->hasAttribute(ResolverCollection::getName())) {
            StaticLoggerFacade::notice(
                "Can't find type resolver in node:\n"
                . NodeTypeManagerTrait::resolveNodeTreeAsJson($node),
                [__METHOD__]
            );

            return;
        }

        self::addTypeCollectionToNode($node, self::resolveType($node));
    }
}
