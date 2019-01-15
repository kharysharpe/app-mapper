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

use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\AstNodeNotFoundException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\NotImplementedException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\UnknownFqcnException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeCollection;
use Hgraca\PhpExtension\String\StringHelper;
use PhpParser\Node;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\LNumber;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Trait_;
use function implode;

final class TypeFactory
{
    // FIXME should be possible to add to this in the config file
    private $functionList = [
        'array_unique' => ['return' => 'array'],
        'count' => ['return' => 'int'],
        'ctype_digit' => ['return' => 'bool'],
        'filter' => ['return' => 'array'],
        'implode' => ['return' => 'string'],
        'preg_replace' => ['return' => 'string'],
        'reset' => ['return' => 'mixed'],
        'sprintf' => ['return' => 'string'],
        'strpbrk' => ['return' => 'string'],
        'strrpos' => ['return' => 'string'],
        'substr' => ['return' => 'string'],
    ];

    /**
     * @var NodeCollection
     */
    private $nodeCollection;

    public function __construct(NodeCollection $nodeCollection)
    {
        /* @noinspection UnusedConstructorDependenciesInspection Used in trait */
        $this->nodeCollection = $nodeCollection;
    }

    public function buildTypeCollection(?Node $node): TypeCollection
    {
        switch (true) {
            case $node instanceof Class_:
                $type = new Type($this->buildFqcn($node->namespacedName), $node);
                break;
            case $node instanceof Interface_:
                $type = new Type($this->buildFqcn($node->namespacedName), $node);
                break;
            case $node instanceof Trait_:
                $type = new Type($this->buildFqcn($node->namespacedName), $node);
                break;
            case $node instanceof Identifier:
                $type = $this->buildTypeFromIdentifier($node);
                break;
            case $node instanceof Name:
                $type = $this->buildTypeFromName($node);
                break;
            case $node instanceof New_:
                return $this->buildTypeFromNew($node);
                break;
            case $node instanceof Param:
                return $this->buildTypeFromParam($node);
                break;
            case $node instanceof ConstFetch:
                $type = $this->buildTypeFromConstFetch($node);
                break;
            case $node instanceof FuncCall:
                $type = $this->buildTypeFromFuncCall($node);
                break;
            case $node instanceof Variable:
                $type = $this->buildTypeFromVariable($node);
                break;
            case $node instanceof NullableType:
                return $this->buildTypeFromNullableType($node);
                break;
            case $node instanceof String_:
                $type = new Type('string');
                break;
            case $node instanceof LNumber:
                $type = new Type('int');
                break;
            case $node === null:
                return new TypeCollection();
                break;
            default:
                throw NotImplementedException::constructFromNode($node);
        }

        return new TypeCollection($type);
    }

    public function canBuildTypeFor(?Node $node): bool
    {
        return $node instanceof Class_
            || $node instanceof Interface_
            || $node instanceof Trait_
            || $node instanceof Identifier
            || $node instanceof Name
            || $node instanceof New_
            || $node instanceof Param
            || $node instanceof ConstFetch
            || $node instanceof FuncCall
            || $node instanceof Variable
            || $node instanceof NullableType
            || $node instanceof String_
            || $node instanceof LNumber
            || $node === null;
    }

    public function buildSelfType(Node $node): Type
    {
        $classAst = $this->getParentClassAst($node);

        return new Type($this->buildFqcn($classAst->namespacedName), $classAst);
    }

    public function buildNull(): Type
    {
        return new Type('null');
    }

    public function buildTypeFromString(string $string): Type
    {
        if ($string === 'self' || $string === 'this') {
            throw new UnknownFqcnException("Can't create the type from '$string'.");
        }

        if (StringHelper::hasEnding('[]', $string)) {
            return new Type('array', null, $this->buildTypeFromString(StringHelper::removeFromEnd('[]', $string)));
        }

        try {
            return new Type($string, $this->nodeCollection->getAstNode($string));
        } catch (AstNodeNotFoundException $e) {
            return new Type($string);
        }
    }

    private function getParentClassAst(Node $node): Class_
    {
        $classNode = $node;
        while (!$classNode instanceof Class_) {
            $classNode = $classNode->getAttribute('parentNode');
        }

        return $classNode;
    }

    private function buildFqcn(Name $name): string
    {
        if ($name->hasAttribute('resolvedName')) {
            /** @var FullyQualified $fullyQualified */
            $fullyQualified = $name->getAttribute('resolvedName');

            return $fullyQualified->toCodeString();
        }

        return implode('\\', $name->parts);
    }

    private function buildTypeFromIdentifier(Identifier $identifier): Type
    {
        return new Type($identifier->name, null);
    }

    private function buildTypeFromName(Name $name): Type
    {
        $fqcn = $this->buildFqcn($name);

        if ($fqcn === 'self' || $fqcn === 'this') {
            return $this->buildSelfType($name);
        }

        try {
            return new Type($fqcn, $this->nodeCollection->getAstNode($fqcn));
        } catch (AstNodeNotFoundException $e) {
            return new Type($fqcn);
        }
    }

    private function buildTypeFromVariable(Variable $variable): Type
    {
        if ($variable->name === 'this') {
            return $this->buildSelfType($variable);
        }

        throw NotImplementedException::constructFromNode($variable);
    }

    private function buildTypeFromNew(New_ $new): TypeCollection
    {
        return $this->buildTypeCollection($new->class);
    }

    private function buildTypeFromParam(Param $param): TypeCollection
    {
        return $this->buildTypeCollection($param->type)
            ->addTypeCollection($this->buildTypeCollection($param->default));
    }

    private function buildTypeFromConstFetch(ConstFetch $constFetchNode): Type
    {
        $name = (string) $constFetchNode->name;

        if ($name === 'true' || $name === 'false') {
            return new Type('bool');
        }

        if ($name === 'null') {
            return $this->buildNull();
        }

        return new Type($name);
    }

    private function buildTypeFromFuncCall(FuncCall $funcCallNode): Type
    {
        if ($this->isKnownNativeFunction($funcCallNode)) {
            return new Type($this->functionList[$this->getFuncCallName($funcCallNode)]['return']);
        }

        throw new NotImplementedException('Unknown native function \'' . $this->getFuncCallName($funcCallNode) . '\'');
    }

    private function buildTypeFromNullableType(NullableType $nullableType): TypeCollection
    {
        $typeCollection = $this->buildTypeCollection($nullableType->type)
            ->addType($this->buildNull());

        return $typeCollection;
    }

    private function isKnownNativeFunction(FuncCall $funcCallNode): bool
    {
        return array_key_exists($this->getFuncCallName($funcCallNode), $this->functionList);
    }

    private function getFuncCallName(FuncCall $funcCallNode): string
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
