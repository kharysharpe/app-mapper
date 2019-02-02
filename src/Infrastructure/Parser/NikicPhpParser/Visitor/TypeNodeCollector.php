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

use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeDecoratorAccessorTrait;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\AbstractNodeDecorator;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\NamedNodeDecoratorInterface;
use function array_values;

final class TypeNodeCollector
{
    use NodeDecoratorAccessorTrait;

    /**
     * @var AbstractNodeDecorator[][]
     */
    private $collector = [];

    public function collectNodeFor(NamedNodeDecoratorInterface $nodeDecorator): void
    {
        $this->collector[$nodeDecorator->getName()][spl_object_hash($nodeDecorator)] = $nodeDecorator;
    }

    public function reassign(NamedNodeDecoratorInterface $nodeDecorator): void
    {
        $this->collector[$nodeDecorator->getName()] = [];
        $this->collectNodeFor($nodeDecorator);
    }

    /**
     * @return AbstractNodeDecorator[]
     */
    public function getNodesFor(NamedNodeDecoratorInterface $nodeDecorator): array
    {
        return array_values($this->collector[$nodeDecorator->getName()] ?? []);
    }

    public function reset(): void
    {
        $this->collector = [];
    }
}
