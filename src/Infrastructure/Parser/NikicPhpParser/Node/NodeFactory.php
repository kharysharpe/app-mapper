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

namespace Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Node;

use Hgraca\ContextMapper\Core\Port\Parser\Node\AdapterNodeInterface;
use Hgraca\ContextMapper\Core\Port\Parser\Node\TypeNodeInterface;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use function is_string;

final class NodeFactory
{
    /**
     * @param null|string|Node $parserNode
     */
    public static function constructNodeAdapter($parserNode): AdapterNodeInterface
    {
        switch (true) {
            case $parserNode instanceof ClassMethod: // this needs to be above the `Expr`
                return new MethodAdapter($parserNode);
            case $parserNode instanceof MethodCall:
                return new MethodCallAdapter($parserNode);
            case $parserNode instanceof Class_:
            case $parserNode instanceof Expr: // MethodArgument
            case self::isFullyQualifiedName($parserNode):
                return self::constructTypeNodeAdapter($parserNode);
            default:
                return new UnknownTypeNode($parserNode);
        }
    }

    /**
     * @param null|string|Node $parserNode
     */
    public static function constructTypeNodeAdapter($parserNode): TypeNodeInterface
    {
        switch (true) {
            case $parserNode instanceof Class_:
                return new ClassAdapter($parserNode);
            case $parserNode instanceof Expr:
                return new MethodArgumentAdapter($parserNode);
            case self::isFullyQualifiedName($parserNode):
                return new FullyQualifiedTypeNode($parserNode);
            default:
                return new UnknownTypeNode($parserNode);
        }
    }

    private static function isFullyQualifiedName($string): bool
    {
        return is_string($string)
            && !in_array(
                $string,
                ['boolean', 'integer', 'float', 'string', 'array', 'object', 'callable', 'iterable', 'resource', 'null'],
                true
            );
    }
}
