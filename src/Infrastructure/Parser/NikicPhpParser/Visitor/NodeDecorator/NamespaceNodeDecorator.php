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
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Use_;
use function array_filter;

/**
 * @property Namespace_ $node
 */
final class NamespaceNodeDecorator extends AbstractNodeDecorator implements NamedNodeDecoratorInterface
{
    public function __construct(Namespace_ $node)
    {
        parent::__construct($node);
    }

    public function resolveTypeCollection(): TypeCollection
    {
        return new TypeCollection();
    }

    public function getName(): string
    {
        return (string) $this->node->name;
    }

    /**
     * @return UseNodeDecorator[]
     */
    public function getUses(): array
    {
        $useList = array_filter(
            $this->node->stmts,
            function (Stmt $stmt) {
                return $stmt instanceof Use_;
            }
        );

        $result = [];
        foreach ($useList as $use) {
            $result[] = $this->getNodeDecorator($use);
        }

        return $result;
    }
}
