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
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeFactory;
use PhpParser\Node;

final class NodeStrategy extends AbstractStrategy
{
    use NodeTypeManagerTrait;

    /**
     * @var TypeFactory
     */
    private $typeFactory;

    public function __construct(TypeFactory $typeFactory)
    {
        $this->typeFactory = $typeFactory;
    }

    public function enterNode(Node $node): void
    {
        $this->validateNode($node);

        if (!$this->typeFactory->canBuildTypeFor($node)) {
            return;
        }

        self::addTypeResolver(
            $node,
            function () use ($node): TypeCollection {
                return $this->typeFactory->buildTypeCollection($node);
            }
        );
    }

    public static function getNodeTypeHandled(): string
    {
        return Node::class;
    }
}
