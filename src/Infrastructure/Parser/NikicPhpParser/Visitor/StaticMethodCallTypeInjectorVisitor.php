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

use Hgraca\ContextMapper\Core\Port\Logger\StaticLoggerFacade;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Exception\MethodNotFoundInClassException;
use PhpParser\Node;
use PhpParser\Node\Expr\StaticCall;

final class StaticMethodCallTypeInjectorVisitor extends AbstractTypeInjectorVisitor
{
    public function enterNode(Node $node): void
    {
        parent::enterNode($node);
        if ($node instanceof StaticCall) {
            $this->addTypeCollectionToStaticCall($node);
        }
    }

    private function addTypeCollectionToStaticCall(StaticCall $staticCall): void
    {
        $classTypeCollection = self::getTypeCollectionFromNode($staticCall->class);

        /** @var Type $classType */
        foreach ($classTypeCollection as $classType) {
            if (!$classType->hasAst()) {
                $this->addTypeToNode($staticCall, Type::constructUnknownFromNode($staticCall));
                continue;
            }
            try {
                $returnTypeNode = $classType->getAstMethod((string) $staticCall->name)->returnType;
                if ($returnTypeNode === null) {
                    $this->addTypeToNode($staticCall, Type::constructVoid());
                } else {
                    $classMethodReturnTypeCollection = self::getTypeCollectionFromNode($returnTypeNode);
                    $this->addTypeCollectionToNode($staticCall, $classMethodReturnTypeCollection);
                }
            } catch (MethodNotFoundInClassException $e) {
                StaticLoggerFacade::warning(
                    "Silently ignoring a MethodNotFoundInClassException in this visitor.\n"
                    . "We get exceptions when trying to get a method but it is defined in a parent class or trait.\n"
                    . "This should be fixed in the type addition visitors.\n"
                    . $e->getMessage(),
                    [__METHOD__]
                );
                $this->addTypeToNode($staticCall, Type::constructUnknownFromNode($staticCall));
            }
        }
    }
}
