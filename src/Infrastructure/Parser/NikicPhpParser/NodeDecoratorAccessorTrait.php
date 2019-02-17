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

namespace Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser;

use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\AbstractNodeDecorator;
use PhpParser\Node;

trait NodeDecoratorAccessorTrait
{
    private static $DECORATOR_ATTRIBUTE = 'decorator';

    protected function setNodeDecorator(Node $node, AbstractNodeDecorator $nodeDecorator): void
    {
        $node->setAttribute(self::$DECORATOR_ATTRIBUTE, $nodeDecorator);
    }

    protected function getNodeDecorator(Node $node): AbstractNodeDecorator
    {
        return $node->getAttribute(self::$DECORATOR_ATTRIBUTE);
    }
}
