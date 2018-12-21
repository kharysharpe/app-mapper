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
use PhpParser\Node\Expr\StaticCall;

final class StaticCallClassTypeInjectorVisitor extends AbstractTypeInjectorVisitor
{
    public function enterNode(Node $node): void
    {
        parent::enterNode($node);
        if ($node instanceof StaticCall) {
            $this->addTypeToStaticCallClass($node);
        }
    }

    private function addTypeToStaticCallClass(StaticCall $staticCall): void
    {
        $this->addTypeToNode(
            $staticCall->class,
            $this->buildType($staticCall->class)
        );
    }
}
