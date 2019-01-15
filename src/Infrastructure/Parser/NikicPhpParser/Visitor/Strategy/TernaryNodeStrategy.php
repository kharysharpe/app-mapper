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

namespace Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Strategy;

use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeTypeManagerTrait;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeCollection;
use PhpParser\Node;
use PhpParser\Node\Expr\Ternary;

final class TernaryNodeStrategy extends AbstractStrategy
{
    use NodeTypeManagerTrait;

    /**
     * @param Node|Ternary $ternaryNode
     */
    public function enterNode(Node $ternaryNode): void
    {
        $this->validateNode($ternaryNode);

        self::addTypeResolver(
            $ternaryNode,
            function () use ($ternaryNode): TypeCollection {
                $typeCollection = $ternaryNode->if === null
                    ? self::getTypeCollectionFromNode($ternaryNode->cond)
                    : self::getTypeCollectionFromNode($ternaryNode->if);

                return $typeCollection->addTypeCollection(self::getTypeCollectionFromNode($ternaryNode->else));
            }
        );
    }

    public static function getNodeTypeHandled(): string
    {
        return Ternary::class;
    }
}
