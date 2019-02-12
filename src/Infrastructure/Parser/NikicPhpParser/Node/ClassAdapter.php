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

namespace Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Node;

use Hgraca\AppMapper\Core\Port\Parser\Exception\ParserException;
use Hgraca\AppMapper\Core\Port\Parser\Node\ClassInterface;
use Hgraca\AppMapper\Core\Port\Parser\Node\MethodInterface;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\AbstractNodeDecorator;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\InterfaceNodeDecorator;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\NameNodeDecorator;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\StmtClassNodeDecorator;
use Hgraca\PhpExtension\String\ClassHelper;
use function array_keys;
use function array_merge;
use function get_class;

final class ClassAdapter implements ClassInterface
{
    /**
     * @var StmtClassNodeDecorator
     */
    private $classNodeDecorator;

    /**
     * @var StmtClassNodeDecorator[]|null[]
     */
    private $parentList;

    /**
     * @var InterfaceNodeDecorator[]|null[]
     */
    private $implementedList;

    private function __construct()
    {
    }

    public static function constructFromClassNode(StmtClassNodeDecorator $classNodeDecorator): self
    {
        $self = new self();

        $self->classNodeDecorator = $classNodeDecorator;

        return $self;
    }

    public function getFullyQualifiedType(): string
    {
        return $this->classNodeDecorator->getTypeCollection()->getUniqueType()->getFqn();
    }

    public function getCanonicalType(): string
    {
        return ClassHelper::extractCanonicalClassName($this->getFullyQualifiedType());
    }

    public function getMethod(string $methodName): MethodInterface
    {
        return new MethodAdapter($this->classNodeDecorator->getMethod($methodName));
    }

    /**
     * @return MethodInterface[]
     */
    public function getMethodList(): array
    {
        $methodList = [];
        foreach ($this->classNodeDecorator->getMethods() as $methodNodeDecorator) {
            $methodList[] = new MethodAdapter($methodNodeDecorator);
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
                $this->getAllInterfacesFullyQualifiedNameList($this->classNodeDecorator),
                $this->getAllParentsInterfacesFullyQualifiedNameList()
            )
        );
    }

    public function __toString(): string
    {
        return $this->getFullyQualifiedType();
    }

    private function getAllParentsFullyQualifiedNameList(): array
    {
        if ($this->parentList === null) {
            $this->parentList = $this->findAllParentsFullyQualifiedNameListRecursively($this->classNodeDecorator);
        }

        return $this->parentList;
    }

    private function getAllInterfacesFullyQualifiedNameList(StmtClassNodeDecorator $classNodeDecorator): array
    {
        if ($this->implementedList === null) {
            $implementedList = [];
            foreach ($classNodeDecorator->getInterfaces() as $interfaceNameNodeDecorator) {
                $interfaceType = $interfaceNameNodeDecorator->getTypeCollection()->getUniqueType();
                $implementedList[] = [
                    $interfaceType->toString() => $interfaceType->hasNode() ? $interfaceType->getNodeDecorator() : null,
                ];
                if ($interfaceType->hasNode()) {
                    $implementedList[] = $this->findAllParentsFullyQualifiedNameListRecursively(
                        $interfaceType->getNodeDecorator()
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
     * TODO this method is dirty, it should be split in 2, one for classes and another one for interfaces
     *
     * @return AbstractNodeDecorator[]
     */
    private function findAllParentsFullyQualifiedNameListRecursively(AbstractNodeDecorator $nodeDecorator): array
    {
        if (!$nodeDecorator instanceof StmtClassNodeDecorator && !$nodeDecorator instanceof InterfaceNodeDecorator) {
            throw new ParserException(
                'Only classes and interfaces can have parents, the given node is of type ' . get_class($nodeDecorator)
            );
        }

        $parentNameNodeList = $nodeDecorator->getParentNameList();

        $parentList = [];
        /** @var NameNodeDecorator $parentNameNode */
        foreach ($parentNameNodeList as $parentNameNode) {
            $parentType = $parentNameNode->getTypeCollection()->getUniqueType();
            $parentList[] = [
                $parentType->toString() => $parentType->hasNode() ? $parentType->getNodeDecorator() : null,
            ];

            if (!$parentType->hasNode()) {
                continue;
            }

            $parentNodeDecorator = $parentType->getNodeDecorator();
            if (
                $nodeDecorator instanceof StmtClassNodeDecorator
                && !$parentNodeDecorator instanceof StmtClassNodeDecorator
            ) {
                throw new ParserException(
                    'A class can only be extend another class, the given parent is of type '
                    . get_class($parentNodeDecorator)
                );
            }
            if (
                $nodeDecorator instanceof InterfaceNodeDecorator
                && !$parentNodeDecorator instanceof InterfaceNodeDecorator
            ) {
                throw new ParserException(
                    'A interface can only be extend another interface, the given parent is of type ' . get_class(
                        $parentNodeDecorator
                    )
                );
            }

            $parentList[] = $this->findAllParentsFullyQualifiedNameListRecursively($parentNodeDecorator);
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
