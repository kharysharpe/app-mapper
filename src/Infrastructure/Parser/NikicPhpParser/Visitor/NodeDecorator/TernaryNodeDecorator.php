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

namespace Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator;

use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeCollection;
use PhpParser\Node\Expr\Ternary;

/**
 * @property Ternary $node
 */
final class TernaryNodeDecorator extends AbstractNodeDecorator
{
    public function __construct(Ternary $node, AbstractNodeDecorator $parentNode)
    {
        parent::__construct($node, $parentNode);
    }

    public function resolveTypeCollection(): TypeCollection
    {
        $typeCollection = !$this->hasIf()
            ? $this->getCond()->getTypeCollection()
            : $this->getIf()->getTypeCollection();

        return $typeCollection->addTypeCollection($this->getElse()->getTypeCollection());
    }

    private function getCond(): AbstractNodeDecorator
    {
        return $this->getNodeDecorator($this->node->cond);
    }

    private function hasIf(): bool
    {
        return $this->node->if !== null;
    }

    private function getIf(): AbstractNodeDecorator
    {
        return $this->getNodeDecorator($this->node->if);
    }

    private function getElse(): AbstractNodeDecorator
    {
        return $this->getNodeDecorator($this->node->else);
    }
}
