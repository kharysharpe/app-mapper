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
        'array_unique' => ['return' => 'array'],
        'count' => ['return' => 'int'],
        'ctype_digit' => ['return' => 'bool'],
        'filter' => ['return' => 'array'],
        'implode' => ['return' => 'string'],
        'iterator_to_array' => ['return' => 'array'],
        'preg_replace' => ['return' => 'string'],
        'reset' => ['return' => 'mixed'],
        'sprintf' => ['return' => 'string'],
        'strpbrk' => ['return' => 'string'],
        'strrpos' => ['return' => 'string'],
        'substr' => ['return' => 'string'],
        'trigger_error' => ['return' => 'bool'],
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
            return new TypeCollection(
                new Type(self::FUNCTION_LIST[$this->getFunctionName($this->node)]['return'])
            );
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
