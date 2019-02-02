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
use PhpParser\Node\Expr\PropertyFetch;

/**
 * @property PropertyFetch $node
 */
final class PropertyFetchNodeDecorator extends AbstractNodeDecorator implements NamedNodeDecoratorInterface
{
    use AssignableNodeTrait;

    public function resolveTypeCollection(): TypeCollection
    {
        if ($this->isAssignee()) {
            /** @var AssignNodeDecorator $parentNode */
            $parentNode = $this->getParentNode();

            return $parentNode->getExpression()->getTypeCollection();
        }

        return $this->getSiblingTypeCollection();
    }

    public function getName(): string
    {
        return (string) $this->node->name;
    }
}
