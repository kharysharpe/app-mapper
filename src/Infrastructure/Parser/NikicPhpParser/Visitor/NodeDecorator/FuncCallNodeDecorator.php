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

use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\NotImplementedException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Type;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeCollection;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use ReflectionFunction;

/**
 * @property FuncCall $node
 */
final class FuncCallNodeDecorator extends AbstractNodeDecorator implements NamedNodeDecoratorInterface
{
    // FIXME should be possible to add to this in the config file
    private const FUNCTION_LIST = [
        'array_column' => ['return' => ['array']],
        'array_filter' => ['return' => ['array']],
        'array_flip' => ['return' => ['array']],
        'array_intersect' => ['return' => ['array']],
        'array_key_exists' => ['return' => ['bool']],
        'array_keys' => ['return' => ['array']],
        'array_map' => ['return' => ['array']],
        'array_merge' => ['return' => ['array']],
        'array_reduce' => ['return' => ['array']],
        'array_search' => ['return' => ['bool', 'int', 'string']],
        'array_shift' => ['return' => ['array']],
        'array_slice' => ['return' => ['array']],
        'array_unique' => ['return' => ['array']],
        'array_values' => ['return' => ['array']],
        'ceil' => ['return' => ['float']],
        'count' => ['return' => ['int']],
        'ctype_digit' => ['return' => ['bool']],
        'date' => ['return' => ['bool', 'string']],
        'end' => ['return' => ['mixed']],
        'explode' => ['return' => ['array']],
        'file_put_contents' => ['return' => ['int', 'bool']],
        'filter' => ['return' => ['array']],
        'floor' => ['return' => ['float']],
        'get_class' => ['return' => ['string']],
        'gettype' => ['return' => ['string']],
        'implode' => ['return' => ['string']],
        'in_array' => ['return' => ['bool']],
        'invoke' => ['return' => ['array']], // functional-php
        'invoker' => ['return' => ['callable']], // functional-php
        'is_array' => ['return' => ['bool']],
        'is_string' => ['return' => ['bool']],
        'iterator_to_array' => ['return' => ['array']],
        'json_encode' => ['return' => ['string']],
        'json_decode' => ['return' => ['object', 'array']],
        'map' => ['return' => ['array']], // functional-php
        'max' => ['return' => ['int', 'float']],
        'mb_strimwidth' => ['return' => ['string']],
        'mb_substr' => ['return' => ['string']],
        'md5' => ['return' => ['string']],
        'parse_url' => ['return' => ['array', 'bool']],
        'pathinfo' => ['return' => ['array', 'string']],
        'preg_match' => ['return' => ['bool', 'int']],
        'preg_replace' => ['return' => ['string']],
        'reset' => ['return' => ['mixed']],
        'round' => ['return' => ['float']],
        'sprintf' => ['return' => ['string']],
        'strpbrk' => ['return' => ['string']],
        'strlen' => ['return' => ['int']],
        'strrpos' => ['return' => ['string']],
        'strtolower' => ['return' => ['string']],
        'strtotime' => ['return' => ['int', 'bool']],
        'strtoupper' => ['return' => ['string']],
        'substr' => ['return' => ['string']],
        'sys_get_temp_dir' => ['return' => ['string']],
        'trigger_error' => ['return' => ['bool']],
        'trim' => ['return' => ['string']],
        'uasort' => ['return' => ['bool']],
        'usort' => ['return' => ['bool']],
        'var_export' => ['return' => ['null', 'string']],
    ];

    public function __construct(FuncCall $node, AbstractNodeDecorator $parentNode)
    {
        parent::__construct($node, $parentNode);
    }

    public function getName(): string
    {
        return (string) $this->node->name;
    }

    public function resolveTypeCollection(): TypeCollection
    {
        if ($this->isKnownNativeFunction($this->node)) {
            $typeList = [];
            foreach (self::FUNCTION_LIST[$this->getFunctionName($this->node)]['return'] as $returnType) {
                $typeList[] = new Type($returnType);
            }

            return new TypeCollection(...$typeList);
        }

        $typeFromReflection = (string) (new ReflectionFunction($this->getFunctionName($this->node)))->getReturnType();
        if ($typeFromReflection) {
            return new TypeCollection(new Type($typeFromReflection));
        }

        throw new NotImplementedException('Unknown native function \'' . $this->getFunctionName($this->node) . '\'');
    }

    private function isKnownNativeFunction(FuncCall $funcCallNode): bool
    {
        return array_key_exists($this->getFunctionName($funcCallNode), self::FUNCTION_LIST);
    }

    private function getFunctionName(FuncCall $funcCallNode): string
    {
        if ($funcCallNode->name instanceof Name) {
            return (string) $funcCallNode->name;
        }

        if (property_exists($funcCallNode->name, 'name')) {
            return $funcCallNode->name->name;
        }

        throw new NotImplementedException('Can\'t get the name of this function call');
    }
}
