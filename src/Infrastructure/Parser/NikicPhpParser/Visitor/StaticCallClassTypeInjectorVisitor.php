<?php

declare(strict_types=1);

/*
 * This file is part of the Context Mapper application,
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

namespace Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Visitor;

use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\AstMap;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\NodeVisitorAbstract;

class StaticCallClassTypeInjectorVisitor extends NodeVisitorAbstract implements AstConnectorVisitorInterface
{
    /**
     * @var AstMap
     */
    private $astMap;

    public function __construct(AstMap $astMap)
    {
        $this->astMap = $astMap;
    }

    public function enterNode(Node $staticCall): void
    {
        if (!$staticCall instanceof StaticCall) {
            return;
        }

        $class = $staticCall->class;
        /** @var FullyQualified $name */
        $name = $class->getAttribute('resolvedName');
        $fqcn = $name->toCodeString();
        if ($this->astMap->hasAstNode($fqcn)) {
            $class->setAttribute(self::AST_KEY, $this->astMap->getAstNode($fqcn));
        }
    }
}
