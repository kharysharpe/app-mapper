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

use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\InvalidArgumentException;
use PhpParser\Node;
use function get_class;

abstract class AbstractStrategy implements NodeVisitorStrategyInterface
{
    public function enterNode(Node $node): void
    {
        $this->validateNode($node);
    }

    public function leaveNode(Node $node): void
    {
        $this->validateNode($node);
    }

    protected function validateNode(Node $node): void
    {
        $handledNodeClass = static::getNodeTypeHandled();
        if (!$node instanceof $handledNodeClass) {
            throw new InvalidArgumentException(
                'Visitor strategy \'' . static::class . '\' can not visit node of type \'' . get_class($node) . '\''
            );
        }
    }
}