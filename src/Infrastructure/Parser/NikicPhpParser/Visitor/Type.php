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

use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\MethodNotFoundInClassException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeTypeManagerTrait;
use Hgraca\PhpExtension\String\ClassHelper;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Trait_;

final class Type
{
    public const UNKNOWN = 'Unknown';

    /**
     * @var string
     */
    private $typeAsString;

    /**
     * @var Class_|Interface_|Trait_|null
     */
    private $ast;

    private $nestedType;

    public function __construct(string $typeAsString, ?Node $ast = null, self $nestedType = null)
    {
        $this->typeAsString = ltrim($typeAsString, '\\');
        $this->ast = $ast;
        $this->nestedType = $nestedType;
    }

    public static function constructUnknownFromNode(Node $node): self
    {
        return new self(self::UNKNOWN, $node);
    }

    public static function constructVoid(): self
    {
        return new self('void');
    }

    public static function getName(): string
    {
        return ClassHelper::extractCanonicalClassName(__CLASS__);
    }

    public function getFqn(): string
    {
        return $this->typeAsString;
    }

    public function toString(): string
    {
        return (string) $this;
    }

    public function __toString(): string
    {
        return $this->typeAsString;
    }

    public function getAst(): ?Node
    {
        return $this->ast;
    }

    public function hasAst(): bool
    {
        return $this->ast !== null;
    }

    public function getAstMethod(string $methodName): ClassMethod
    {
        foreach ($this->ast->stmts as $stmt) {
            if (
                $stmt instanceof ClassMethod
                && (string) $stmt->name === $methodName
            ) {
                return $stmt;
            }
        }

        throw MethodNotFoundInClassException::constructFromFqcn($methodName, $this->typeAsString);
    }

    public function hasAstMethod(string $methodName): bool
    {
        foreach ($this->ast->stmts as $stmt) {
            if (
                $stmt instanceof ClassMethod
                && (string) $stmt->name === $methodName
            ) {
                return true;
            }
        }

        return false;
    }

    public function isEqualTo(self $otherType): bool
    {
        return $this->typeAsString === $otherType->typeAsString;
    }

    public function hasNestedType(): bool
    {
        return (bool) $this->nestedType;
    }

    public function getNestedType(): self
    {
        return $this->nestedType;
    }

    public function getNodeTreeAsJson(): string
    {
        return $this->ast ? NodeTypeManagerTrait::resolveNodeTreeAsJson($this->ast) : '';
    }
}
