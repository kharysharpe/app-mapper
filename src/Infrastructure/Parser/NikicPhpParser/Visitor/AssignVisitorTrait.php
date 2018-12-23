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

use PhpParser\Node\Expr\Assign;

/**
 * @mixin AbstractTypeInjectorVisitor
 */
trait AssignVisitorTrait
{
    private function leaveAssignNode(Assign $assignNode): void
    {
        if (!self::hasTypeCollection($assignNode->expr)) {
            // TODO stop ignoring unresolved and resolve all detected
            return;
        }
        $this->addTypeCollectionToNode($assignNode->var, self::getTypeCollectionFromNode($assignNode->expr));
        $this->collectVariableTypes($assignNode->var);
    }
}
