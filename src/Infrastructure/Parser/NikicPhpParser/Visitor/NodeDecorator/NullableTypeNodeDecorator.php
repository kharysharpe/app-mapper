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

use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Type;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeCollection;
use PhpParser\Node\NullableType;

/**
 * @property NullableType $node
 */
final class NullableTypeNodeDecorator extends AbstractNodeDecorator
{
    public function __construct(NullableType $node, AbstractNodeDecorator $parentNode)
    {
        parent::__construct($node, $parentNode);
    }

    public function resolveTypeCollection(): TypeCollection
    {
        return $this->getAlternativeType()->getTypeCollection()->addType(Type::constructNull());
    }

    private function getAlternativeType(): AbstractNodeDecorator
    {
        return $this->getNodeDecorator($this->node->type);
    }
}
