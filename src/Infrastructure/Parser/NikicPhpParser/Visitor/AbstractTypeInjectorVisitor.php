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
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeCollection;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeTypeManagerTrait;
use Hgraca\PhpExtension\Type\TypeHelper;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeVisitorAbstract;
use function implode;
use function is_string;

abstract class AbstractTypeInjectorVisitor extends NodeVisitorAbstract
{
    use NodeTypeManagerTrait;
    use PropertyCollectorTrait;
    use VariableTypeCollectorTrait;

    /**
     * @var NodeCollection
     */
    protected $astCollection;

    public function __construct(NodeCollection $astCollection)
    {
        /* @noinspection UnusedConstructorDependenciesInspection Used in trait */
        $this->astCollection = $astCollection;
    }

    public function leaveNode(Node $node): void
    {
        switch (true) {
            case $node instanceof ClassMethod:
                $this->resetCollectedVariableTypes();
                break;
            case $node instanceof Class_:
                $this->resetCollectedPropertyType();
                break;
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
                return Type::constructNull();
                break;
            default:
                throw new NotImplementedException('Can\'t build Type from ' . TypeHelper::getType($node));
        }
    }

    protected function buildTypeFromIdentifier(Identifier $identifier): Type
    {
        return new Type($identifier->name, null);
    }

    protected function buildTypeFromName(Name $name): Type
    {
        $fqcn = $this->buildFqcn($name);

        if ($fqcn === 'self' || $fqcn === 'this') {
            return $this->buildSelfType($name);
        }

        try {
            return new Type($fqcn, $this->astCollection->getAstNode($fqcn));
        } catch (AstNodeNotFoundException $e) {
            return new Type($fqcn);
        }
    }

    protected function buildSelfType(Node $node): Type
    {
        $classAst = $this->getParentClassAst($node);

        return new Type($this->buildFqcn($classAst->namespacedName), $classAst);
    }

    protected function getParentClassAst(Node $node): Class_
    {
        $classNode = $node;
        while (!$classNode instanceof Class_) {
            $classNode = $classNode->getAttribute('parentNode');
        }

        return $classNode;
    }

    protected function buildTypeFromNew(New_ $new): Type
    {
        return $this->buildType($new->class);
    }

    protected function buildTypeFromNullable(NullableType $nullableTypeNode): Type
    {
        return $this->buildType($nullableTypeNode->type);
    }

    private function buildTypeFromParam(Param $param): Type
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

    /**
     * TODO We are only adding properties types in the class itself.
     *      We should fix this by adding them also to the super classes and traits.
     */
    protected function addCollectedPropertiesTypeToTheirDeclaration(Class_ $node): void
    {
        // After collecting app possible class properties, we inject them in their declaration
        foreach ($node->stmts as $property) {
            if (
                $property instanceof Property
                && $this->hasCollectedPropertyType($propertyName = $this->getPropertyName($property))
            ) {
                $this->addTypeCollectionToNode($property, $this->getCollectedPropertyType($propertyName));
            }
        }
        $this->resetCollectedPropertyType();
    }

    protected function collectVariableTypes(Expr $var): void
    {
        $typeCollection = self::getTypeCollectionFromNode($var);
        switch (true) {
            case $var instanceof Variable: // Assignment to variable
                $this->collectVariableType($this->getVariableName($var), $typeCollection);
                break;
            case $var instanceof PropertyFetch: // Assignment to property
                $this->collectPropertyType($this->getPropertyName($var), $typeCollection);
                break;
        }
    }

    protected function addCollectedVariableTypes(Variable $variable): void
    {
        $variableName = $this->getVariableName($variable);

        if (!$this->hasCollectedVariableType($variableName)) {
            return;
        }

        $this->addTypeCollectionToNode(
            $variable,
            $this->getCollectedVariableType($variableName)
        );
    }
}
