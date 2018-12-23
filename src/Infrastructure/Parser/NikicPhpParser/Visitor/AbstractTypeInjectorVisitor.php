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

use Hgraca\AppMapper\Core\SharedKernel\Exception\NotImplementedException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\AstNodeNotFoundException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\TypeNotFoundInNodeException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeCollection;
use Hgraca\PhpExtension\Type\TypeHelper;
use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeVisitorAbstract;
use function implode;
use function is_string;
use function json_encode;

abstract class AbstractTypeInjectorVisitor extends NodeVisitorAbstract implements AstConnectorVisitorInterface
{
    /**
     * @var NodeCollection
     */
    protected $astCollection;

    /**
     * @var Type
     */
    protected $self;

    public function __construct(NodeCollection $astCollection)
    {
        /* @noinspection UnusedConstructorDependenciesInspection Used in trait */
        $this->astCollection = $astCollection;
    }

    public function enterNode(Node $node): void
    {
        if ($node instanceof Class_) {
            $this->self = $this->buildType($node->namespacedName);
        }
    }

    protected function buildType($node): Type
    {
        switch (true) {
            case $node instanceof Class_:
                return new Type($this->buildFqcn($node->namespacedName), $node);
                break;
            case $node instanceof Identifier:
                return $this->buildTypeFromIdentifier($node);
                break;
            case $node instanceof Name:
                return $this->buildTypeFromName($node);
                break;
            case $node instanceof New_:
                return $this->buildTypeFromNew($node);
                break;
            case $node instanceof NullableType:
                return $this->buildTypeFromNullable($node);
                break;
            case $node instanceof Param:
                return $this->buildTypeFromParam($node);
                break;
            case is_string($node):
                return new Type($node);
                break;
            case $node === null:
                return new Type('NULL');
                break;
            default:
                throw new NotImplementedException('Can\'t build Type from ' . TypeHelper::getType($node));
        }
    }

    protected function buildTypeFromIdentifier(Identifier $identifier)
    {
        return new Type($identifier->name, null);
    }

    protected function buildTypeFromName(Name $name): Type
    {
        $fqcn = $this->buildFqcn($name);

        if ($fqcn === 'self' || $fqcn === 'this') {
            return $this->self;
        }

        try {
            return new Type($fqcn, $this->astCollection->getAstNode($fqcn));
        } catch (AstNodeNotFoundException $e) {
            return new Type($fqcn);
        }
    }

    protected function buildTypeFromNew(New_ $new)
    {
        return $this->buildType($new->class);
    }

    protected function buildTypeFromNullable(NullableType $nullableTypeNode): Type
    {
        return $this->buildType($nullableTypeNode->type);
    }

    private function buildTypeFromParam(Param $param)
    {
        return $this->buildType($param->type);
    }

    protected function buildFqcn(Name $name): string
    {
        if ($name->hasAttribute('resolvedName')) {
            /** @var FullyQualified $fullyQualified */
            $fullyQualified = $name->getAttribute('resolvedName');

            return $fullyQualified->toCodeString();
        }

        return implode('\\', $name->parts);
    }

    public function addTypeCollectionToNode(Node $node, TypeCollection $newTypeCollection): void
    {
        if (!$node->hasAttribute(TypeCollection::getName())) {
            $node->setAttribute(TypeCollection::getName(), $newTypeCollection);

            return;
        }

        /** @var TypeCollection $typeCollection */
        $typeCollection = $node->getAttribute(TypeCollection::getName());
        $typeCollection->addTypeCollection($newTypeCollection);
    }

    public function addTypeToNode(Node $node, Type ...$typeList): void
    {
        if (!$node->hasAttribute(TypeCollection::getName())) {
            $typeCollection = new TypeCollection($node);
            $node->setAttribute(TypeCollection::getName(), $typeCollection);
        } else {
            $typeCollection = $node->getAttribute(TypeCollection::getName());
        }

        foreach ($typeList as $type) {
            $typeCollection->addType($type);
        }
    }

    public static function getTypeCollectionFromNode(?Node $node): TypeCollection
    {
        if (!$node) {
            return new TypeCollection();
        }
        if (!$node->hasAttribute(TypeCollection::getName())) {
            $relevantInfo = [];
            $loopNode = $node;
            while ($loopNode->hasAttribute('parentNode')) {
                $relevantInfo[] = get_class($loopNode) . ' => '
                    . (property_exists($loopNode, 'name')
                        ? $loopNode->name
                        : (property_exists($loopNode, 'var') && property_exists($loopNode->var, 'name')
                            ? $loopNode->var->name
                            : 'no_name')
                    );
                $loopNode = $loopNode->getAttribute('parentNode');
            }
            throw new TypeNotFoundInNodeException(
                'Class: ' . static::class . "\n"
                . "Can't find type collection in node:\n"
                . json_encode($relevantInfo, JSON_PRETTY_PRINT)
            );
        }

        return $node->getAttribute(TypeCollection::getName());
    }

    public static function hasTypeCollection(?Node $node): bool
    {
        if (!$node) {
            return true; // because getTypeCollectionFromNode returns an empty collection in case of null
        }

        return $node->hasAttribute(TypeCollection::getName());
    }
}
