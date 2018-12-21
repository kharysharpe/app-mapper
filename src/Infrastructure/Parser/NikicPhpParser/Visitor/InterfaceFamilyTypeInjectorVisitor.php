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

use PhpParser\Node;
use PhpParser\Node\Stmt\Interface_;

final class InterfaceFamilyTypeInjectorVisitor extends AbstractTypeInjectorVisitor
{
    public function enterNode(Node $node): void
    {
        parent::enterNode($node);
        if ($node instanceof Interface_) {
            $this->addTypeToParents($node);
        }
    }

    private function addTypeToParents(Interface_ $interface): void
    {
        if (!empty($interface->extends)) {
            foreach ($interface->extends as $parent) {
                $this->addTypeToNode(
                    $parent,
                    $this->buildType($parent)
                );
            }
        }
    }
}
