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

use Hgraca\ContextMapper\Core\Port\Parser\Exception\ParserException;
use Hgraca\ContextMapper\Core\Port\Parser\Node\ClassInterface;
use Hgraca\ContextMapper\Core\Port\Parser\Node\MethodInterface;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Exception\MethodNotFoundInClassException;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Visitor\AbstractTypeInjectorVisitor;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Type;
use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Interface_;
use function array_keys;
use function array_merge;
use function get_class;
use function is_array;

final class ClassAdapter implements ClassInterface
{
    /**
     * @var Class_
     */
    private $class;

    /**
     * @var Class_[]|null[]
     */
    private $parentList;

    /**
     * @var Interface_[]|null[]
     */
    private $implementedList;

    private function __construct()
    {
    }

    public static function constructFromClassNode(Class_ $class): self
    {
        $self = new self();

        $self->class = $class;

        return $self;
    }

    public static function constructFromNew(New_ $newExpression): self
    {
        /** @var Class_ $class */
        $class = AbstractTypeInjectorVisitor::getTypeFromNode($newExpression)->getAst();

        return self::constructFromClassNode($class);
    }

    public function getFullyQualifiedType(): string
    {
        return ltrim($this->class->namespacedName->toCodeString(), '\\');
    }

    public function getCanonicalType(): string
    {
        return $this->class->name->toString();
    }

    public function getMethod(string $methodName): MethodInterface
    {
        foreach ($this->class->stmts as $stmt) {
            if (
                $stmt instanceof ClassMethod
                && $stmt->name->toString() === $methodName
            ) {
                return new MethodAdapter($stmt);
            }
        }

        throw new MethodNotFoundInClassException($methodName, $this->getFullyQualifiedType());
    }

    /**
     * @return MethodInterface[]
     */
    public function getMethodList(): array
    {
        $methodList = [];
        foreach ($this->class->stmts as $stmt) {
            if ($stmt instanceof ClassMethod) {
                $methodList[] = new MethodAdapter($stmt);
            }
        }

        return $methodList;
    }

    /**
     * @return string[]
     */
    public function getAllFamilyFullyQualifiedNameList(): array
    {
        return array_keys(
            array_merge(
                $this->getAllParentsFullyQualifiedNameList(),
                $this->getAllInterfacesFullyQualifiedNameList($this->class),
                $this->getAllParentsInterfacesFullyQualifiedNameList()
            )
        );
    }

    private function getAllParentsFullyQualifiedNameList(): array
    {
        if ($this->parentList === null) {
            $this->parentList = $this->findAllParentsFullyQualifiedNameListRecursively($this->class);
        }

        return $this->parentList;
    }

    private function getAllInterfacesFullyQualifiedNameList(Class_ $class): array
    {
        if ($this->implementedList === null) {
            $implementedList = [];
            foreach ($class->implements as $interfaceNameNode) {
                /** @var Type $interfaceType */
                $interfaceType = $interfaceNameNode->getAttribute(Type::getName());
                $implementedList[] = [
                    $interfaceType->toString() => $interfaceType->hasAst() ? $interfaceType->getAst() : null,
                ];
                if ($interfaceType->hasAst()) {
                    $implementedList[] = $this->findAllParentsFullyQualifiedNameListRecursively(
                        $interfaceType->getAst()
                    );
                }
            }
            $this->implementedList = !empty($implementedList)
                ? array_merge(...$implementedList)
                : [];
        }

        return $this->implementedList;
    }

    /**
     * @return string[]
     */
    private function findAllParentsFullyQualifiedNameListRecursively(Node $node): array
    {
        if (!$node instanceof Class_ && !$node instanceof Interface_) {
            throw new ParserException(
                'Only classes and interfaces can have parents, the given node is of type ' . get_class($node)
            );
        }

        $parentNameNodeList = $node->extends;
        if (!is_array($parentNameNodeList)) {
            $parentNameNodeList = [$parentNameNodeList];
        }

        $parentList = [];
        foreach ($parentNameNodeList as $parentNameNode) {
            /** @var Type $parentType */
            $parentType = $parentNameNode->getAttribute(Type::getName());
            $parentList[] = [
                $parentType->toString() => $parentType->hasAst() ? $parentType->getAst() : null,
            ];

            if (!$parentType->hasAst()) {
                continue;
            }

            $parentAst = $parentType->getAst();
            if ($node instanceof Class_ && !$parentAst instanceof Class_) {
                throw new ParserException(
                    'A class can only be extend another class, the given parent is of type ' . get_class($parentAst)
                );
            }
            if ($node instanceof Interface_ && !$parentAst instanceof Interface_) {
                throw new ParserException(
                    'A interface can only be extend another interface, the given parent is of type ' . get_class(
                        $parentAst
                    )
                );
            }

            $parentList[] = $this->findAllParentsFullyQualifiedNameListRecursively($parentAst);
        }

        return !empty($parentList) ? array_merge(...$parentList) : [];
    }

    private function getAllParentsInterfacesFullyQualifiedNameList(): array
    {
        $interfaceList = [];
        foreach ($this->parentList as $fqcn => $parentNode) {
            if ($parentNode) {
                $interfaceList[] = $this->getAllInterfacesFullyQualifiedNameList($parentNode);
            }
        }

        return !empty($interfaceList) ? array_merge(...$interfaceList) : [];
    }
}
