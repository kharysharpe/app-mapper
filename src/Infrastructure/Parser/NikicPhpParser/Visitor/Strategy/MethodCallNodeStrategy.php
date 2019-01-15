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

namespace Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Strategy;

use Hgraca\AppMapper\Core\Port\Logger\StaticLoggerFacade;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\MethodNotFoundInClassException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\UnresolvableNodeTypeException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeTypeManagerTrait;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Type;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeCollection;
use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;

final class MethodCallNodeStrategy extends AbstractStrategy
{
    use NodeTypeManagerTrait;

    /**
     * @param Node|MethodCall $methodCall
     */
    public function enterNode(Node $methodCall): void
    {
        $this->validateNode($methodCall);

        self::addTypeResolver(
            $methodCall,
            function () use ($methodCall): TypeCollection {
                $varCollectionType = self::getTypeCollectionFromNode($methodCall->var);

                $typeCollection = new TypeCollection();

                /** @var Type $methodCallVarType */
                foreach ($varCollectionType as $methodCallVarType) {
                    if (!$methodCallVarType->hasAst()) {
                        continue;
                    }

                    try {
                        $returnTypeNode = $methodCallVarType->getAstMethod((string) $methodCall->name)->returnType;
                        $typeCollection = $typeCollection->addTypeCollection(
                            self::getTypeCollectionFromNode($returnTypeNode)
                        );
                    } catch (UnresolvableNodeTypeException $e) {
                        StaticLoggerFacade::warning(
                            "Silently ignoring a UnresolvableNodeTypeException.\n"
                            . 'This is failing, at least, for nested method calls like'
                            . '`$invoice->transactions->first()->getServicePro();`.' . "\n"
                            . "This should be fixed, otherwise we might be missing events.\n"
                            . $e->getMessage(),
                            [__METHOD__]
                        );
                    } catch (MethodNotFoundInClassException $e) {
                        StaticLoggerFacade::warning(
                            "Silently ignoring a MethodNotFoundInClassException.\n"
                            . "This should be fixed, otherwise we might be missing events.\n"
                            . $e->getMessage(),
                            [__METHOD__]
                        );
                    }
                }

                return $typeCollection;
            }
        );
    }

    public static function getNodeTypeHandled(): string
    {
        return MethodCall::class;
    }
}
