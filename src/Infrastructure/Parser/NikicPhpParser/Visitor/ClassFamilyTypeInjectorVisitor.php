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
use PhpParser\Node\Stmt\Class_;

final class ClassFamilyTypeInjectorVisitor extends AbstractTypeInjectorVisitor
{
    public function enterNode(Node $node): void
    {
        parent::enterNode($node);
        if ($node instanceof Class_) {
            $this->addTypeToParent($node);
            $this->addTypeToInterfaces($node);
        }
    }

    private function addTypeToParent(Class_ $class): void
    {
        if (!empty($class->extends)) {
            $parent = $class->extends;
            $this->addTypeToNode(
                $parent,
                $this->buildType($parent)
            );
        }
    }

    private function addTypeToInterfaces(Class_ $class): void
    {
        foreach ($class->implements as $interface) {
            $this->addTypeToNode(
                $interface,
                $this->buildType($interface)
            );
        }
    }
}
