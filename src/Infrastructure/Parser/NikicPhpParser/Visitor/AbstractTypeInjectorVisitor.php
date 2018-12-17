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

namespace Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Visitor;

use Hgraca\ContextMapper\Core\SharedKernel\Exception\NotImplementedException;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Exception\AstNodeNotFoundException;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\NodeCollection;
use Hgraca\PhpExtension\Type\TypeService;
use PhpParser\Node;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeVisitorAbstract;
use function is_string;

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

    // TODO create a new AssignmentFromMethodCallTypeInjectorVisitor and put this there
    // TODO create a new AssignmentFromStaticCallTypeInjectorVisitor and put this there
//    protected function handleAssignmentNode(Assign $assignment): void
//    {
//        $expression = $assignment->expr;
//        $var = $assignment->var;
//        switch (true) {
//            case $expression instanceof MethodCall:
//                // MethodCall on property
//                $methodCall = $expression;
//                $newType = $this->buildType($methodCall);
//                $this->addTypeToNode($methodCall->class, $newType);
//                switch (true) {
//                    case $var instanceof Variable:
//                        // Assignment of method return type to variable
//                        $this->addTypeToNode($var, $newType);
//                        $this->addVariableTypeToBuffer((string) $var->name, $newType);
//                        break;
//                    case $var instanceof PropertyFetch:
//                        // Assignment of method return type to property
//                        $property = $var;
//                        $this->addTypeToNode($property, $newType);
//                        $this->addPropertyTypeToBuffer((string) $property->name, $newType);
//                        break;
//                }
//                break;
//        }
//    }

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
                throw new NotImplementedException('Can\'t build Type from ' . TypeService::getType($node));
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

    public function addTypeToNode(Node $node, Type $type): void
    {
        $node->setAttribute(Type::getName(), $type);
    }

    public static function getTypeFromNode(Node $node): Type
    {
        if (!$node->hasAttribute(Type::getName())) {
            return Type::constructUnknownFromNode($node);
//             throw new TypeNotFoundInNodeException("Can't find type in node " . get_class($node));
        }

        return $node->getAttribute(Type::getName());
    }
}
