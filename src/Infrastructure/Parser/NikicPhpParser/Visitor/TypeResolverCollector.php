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

use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\UnknownVariableException;

final class TypeResolverCollector
{
    private $collector = [];

    public function collectResolver(string $variableName, callable $resolver): void
    {
        if (!$this->hasCollectedResolverCollection($variableName)) {
            $this->setResolverCollection($variableName, (new ResolverCollection())->addResolver($resolver));

            return;
        }

        $this->setResolverCollection(
            $variableName,
            $this->getCollectedResolverCollection($variableName)->addResolver($resolver)
        );
    }

    public function resetResolverCollection(string $variableName, callable $resolver): void
    {
        $this->setResolverCollection($variableName, (new ResolverCollection())->addResolver($resolver));
    }

    public function hasCollectedResolverCollection(string $variableName): bool
    {
        return array_key_exists($variableName, $this->collector);
    }

    public function getCollectedResolverCollection(string $variableName): ResolverCollection
    {
        if (!$this->hasCollectedResolverCollection($variableName)) {
            throw new UnknownVariableException($variableName);
        }

        return $this->collector[$variableName];
    }

    public function resetCollectedResolvers(): void
    {
        $this->collector = [];
    }

    private function setResolverCollection(string $variableName, ResolverCollection $resolverCollection): void
    {
        $this->collector[$variableName] = $resolverCollection;
    }
}
