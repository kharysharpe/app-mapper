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
use PhpParser\Node\Expr\StaticCall;

/**
 * @property StaticCall $node
 */
final class StaticCallNodeDecorator extends AbstractNodeDecorator
{
    public function __construct(StaticCall $node, AbstractNodeDecorator $parentNode)
    {
        parent::__construct($node, $parentNode);
    }

    public function resolveTypeCollection(): TypeCollection
    {
        $calleeTypeCollection = $this->getCallee()->getTypeCollection();

        $staticCallTypeCollection = new TypeCollection();

        /** @var Type $calleeType */
        foreach ($calleeTypeCollection as $calleeType) {
            if (!$calleeType->hasMethod($this->getMethodName())) {
                continue;
            }

            $staticCallTypeCollection = $staticCallTypeCollection->addTypeCollection(
                $this->getReturnTypeCollection($calleeType, $this->getMethodName())
            );
        }

        return $staticCallTypeCollection;
    }

    private function getCallee(): AbstractNodeDecorator
    {
        return $this->getNodeDecorator($this->node->class);
    }

    private function getMethodName(): string
    {
        return (string) $this->node->name;
    }

    private function getReturnTypeCollection(Type $calleeType, string $methodName): TypeCollection
    {
        return $calleeType->getMethod($methodName)->getTypeCollection();
    }
}
