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

use Hgraca\AppMapper\Core\Port\Logger\StaticLoggerFacade;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\MethodNotFoundInClassException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\UnresolvableNodeTypeException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Type;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeCollection;
use PhpParser\Node\Expr\MethodCall;

/**
 * @property MethodCall $node
 */
final class MethodCallNodeDecorator extends AbstractNodeDecorator
{
    public function __construct(MethodCall $node, AbstractNodeDecorator $parentNode)
    {
        parent::__construct($node, $parentNode);
    }

    public function resolveTypeCollection(): TypeCollection
    {
        $calleeTypeCollection = $this->getCallee()->getTypeCollection();
        $methodName = $this->getMethodName();

        $typeCollection = new TypeCollection();
        $countTypesWithoutMethod = 0;

        /** @var Type $calleeType */
        foreach ($calleeTypeCollection as $calleeType) {
            if (!$calleeType->hasAst()) {
                continue;
            }

            try {
                $typeCollection = $typeCollection->addTypeCollection(
                    $this->getReturnTypeCollection($calleeType, $methodName)
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
                ++$countTypesWithoutMethod;
                StaticLoggerFacade::notice(
                    "Silently ignoring a MethodNotFoundInClassException.\n"
                    . "It might be because from a collection of types, only one has the method.\n"
                    . $e->getMessage(),
                    [__METHOD__]
                );
            }
        }

        if ($countTypesWithoutMethod === $calleeTypeCollection->count()) {
//            throw MethodNotFoundInClassException::constructFromCollection($methodName, $calleeTypeCollection);
            StaticLoggerFacade::warning(
                "Silently ignoring a MethodNotFoundInClassException.\n"
                . "Method '$methodName' not found in any of the classes '{$calleeTypeCollection->implodeKeys(', ')}'. "
                . 'It should have been found in at least one.',
                [__METHOD__]
            );
        }

        return $typeCollection;
    }

    public function getCallee(): AbstractNodeDecorator
    {
        return $this->getNodeDecorator($this->node->var);
    }

    public function getMethodName(): string
    {
        return (string) $this->node->name;
    }

    private function getReturnTypeCollection(Type $calleeType, string $methodName): TypeCollection
    {
        return $this->getNodeDecorator(
            $calleeType->getAstMethod($methodName)->returnType
        )->getTypeCollection();
    }
}
