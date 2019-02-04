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

use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\AbstractClassLikeNodeDecorator;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\AbstractNodeDecorator;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\ClassMethodNodeDecorator;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\InterfaceNodeDecorator;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\StmtClassNodeDecorator;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\TraitNodeDecorator;
use Hgraca\PhpExtension\String\ClassHelper;

final class Type
{
    public const UNKNOWN = 'Unknown';

    /**
     * @var string
     */
    private $typeAsString;

    /**
     * @var StmtClassNodeDecorator|TraitNodeDecorator|InterfaceNodeDecorator|null
     */
    private $nodeDecorator;

    private $nestedType;

    private $nodeTree;

    public function __construct(
        string $typeAsString,
        ?AbstractClassLikeNodeDecorator $nodeDecorator = null,
        self $nestedType = null
    ) {
        $this->typeAsString = ltrim($typeAsString, '\\');
        $this->nodeDecorator = $nodeDecorator;
        $this->nestedType = $nestedType;
    }

    public static function constructUnknownFromNode(AbstractNodeDecorator $nodeDecorator): self
    {
        $self = new self(self::UNKNOWN);
        $self->nodeTree = $nodeDecorator->resolveNodeTreeAsJson();

        return $self;
    }

    public static function constructVoid(): self
    {
        return new self('void');
    }

    public static function constructNull(): self
    {
        return new self('null');
    }

    public static function constructBool(): self
    {
        return new self('bool');
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

    public function getNodeDecorator(): ?AbstractNodeDecorator
    {
        return $this->nodeDecorator;
    }

    public function hasNode(): bool
    {
        return $this->nodeDecorator !== null;
    }

    public function getMethod(string $methodName): ClassMethodNodeDecorator
    {
        return $this->nodeDecorator->getMethod($methodName);
    }

    public function hasMethod(string $methodName): bool
    {
        return $this->hasNode()
            ? $this->nodeDecorator->hasMethod($methodName)
            : false;
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

    public function getNodeTree(): string
    {
        if (!$this->nodeTree) {
            $this->nodeTree = $this->nodeDecorator->resolveNodeTreeAsJson();
        }

        return $this->nodeTree;
    }
}
