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
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Type;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeCollection;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;

final class StaticCallNodeStrategy extends AbstractStrategy
{
    use NodeTypeManagerTrait;

    /**
     * @param Node|StaticCall $staticCallNode
     */
    public function enterNode(Node $staticCallNode): void
    {
        $this->validateNode($staticCallNode);

        self::addTypeResolver(
            $staticCallNode,
            function () use ($staticCallNode): TypeCollection {
                $classTypeCollection = self::getTypeCollectionFromNode($staticCallNode->class);

                $staticCallTypeCollection = new TypeCollection();

                /** @var Type $classType */
                foreach ($classTypeCollection as $classType) {
                    if (!$classType->hasAst()) {
                        continue;
                    }

                    if (!$classType->hasAstMethod((string) $staticCallNode->name)) {
                        continue;
                    }

                    $returnTypeNode = $classType->getAstMethod((string) $staticCallNode->name)->returnType;
                    $staticCallTypeCollection = $staticCallTypeCollection->addTypeCollection(
                        self::getTypeCollectionFromNode($returnTypeNode)
                    );
                }

                return $staticCallTypeCollection;
            }
        );
    }

    public static function getNodeTypeHandled(): string
    {
        return StaticCall::class;
    }
}
