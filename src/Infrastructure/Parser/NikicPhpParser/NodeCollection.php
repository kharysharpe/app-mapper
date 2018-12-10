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

namespace Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser;

use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Exception\AstNodeNotFoundException;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Exception\UnitNotFoundInNamespaceException;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Visitor\AssignmentFromNewTypeInjectorVisitor;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Visitor\AssignmentFromParameterTypeInjectorVisitor;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Visitor\ClassFamilyTypeInjectorVisitor;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Visitor\ClassTypeInjectorVisitor;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Visitor\InstantiationTypeInjectorVisitor;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Visitor\InterfaceFamilyTypeInjectorVisitor;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Visitor\MethodParametersTypeInjectorVisitor;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Visitor\MethodReturnTypeInjectorVisitor;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Visitor\ParentConnectorVisitor;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Visitor\PropertyFetchTypeInjectorVisitor;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Visitor\StaticCallClassTypeInjectorVisitor;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Visitor\ThisTypeInjectorVisitor;
use Hgraca\PhpExtension\String\JsonEncoder;
use PhpParser\JsonDecoder;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use function array_key_exists;
use function array_merge;
use function array_values;
use function uniqid;

final class NodeCollection
{
    /**
     * @var string
     */
    private $name;

    /** @var Namespace_[] */
    private $nodeList = [];

    private function __construct()
    {
    }

    public function serializeToFile(string $filePath, bool $prettyPrint = false): void
    {
        file_put_contents($filePath, $this->toSerializedAst($prettyPrint));
    }

    public static function constructFromNodeCollectionList(self ...$nodeCollectionList): self
    {
        $self = new self();
        $self->addCollections(...$nodeCollectionList);

        return $self;
    }

    public static function constructFromFolder(string $folder, string $name = ''): self
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folder));
        $files = new \RegexIterator($files, '/\.php$/');
        $nodeList = [];
        foreach ($files as $file) {
            $nodeList[] = self::parse(file_get_contents($file->getPathName()));
        }

        $self = new self();
        $self->name = $name ?: uniqid('', true);
        $self->nodeList = !empty($nodeList) ? array_merge(...$nodeList) : [];

        return $self;
    }

    public static function unserializeFromFile(string $filePath, string $name = ''): self
    {
        $self = self::fromSerializedAst(file_get_contents($filePath));
        $self->name = $name ?: uniqid('', true);

        return $self;
    }

    public function hasAstNode(string $fqcn): bool
    {
        $key = trim($fqcn, '\\');
        if (!array_key_exists($key, $this->nodeList)) {
            return false;
        }

        return true;
    }

    public function getAstNode(string $fqcn): Node
    {
        $key = trim($fqcn, '\\');
        if (!array_key_exists($key, $this->nodeList)) {
            throw new AstNodeNotFoundException($key);
        }

        return self::getNamespaceUnitNode($this->nodeList[$key]);
    }

    public function toArray(): array
    {
        return $this->nodeList;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function enhance(): void
    {
        $nodeList = array_values($this->nodeList);

        // Add all nodes into the collection
        // Add visitors here if they don't need the final collection
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver(null, ['preserveOriginalNames' => true, 'replaceNodes' => false]));
        $traverser->addVisitor(new ParentConnectorVisitor());
        $traverser->traverse($nodeList);

        // Run visitors that don't need any Types added before hand
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new ClassTypeInjectorVisitor($this)); // TODO test
        $traverser->addVisitor(new ClassFamilyTypeInjectorVisitor($this)); // TODO test
        $traverser->addVisitor(new InterfaceFamilyTypeInjectorVisitor($this)); // TODO test
        $traverser->addVisitor(new StaticCallClassTypeInjectorVisitor($this));
        $traverser->addVisitor(new MethodReturnTypeInjectorVisitor($this));
        $traverser->addVisitor(new ThisTypeInjectorVisitor($this));
        $traverser->addVisitor(new MethodParametersTypeInjectorVisitor($this));
        $traverser->addVisitor(new InstantiationTypeInjectorVisitor($this));
        $traverser->traverse($nodeList);

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new AssignmentFromNewTypeInjectorVisitor($this)); // TODO test
        $traverser->addVisitor(new AssignmentFromParameterTypeInjectorVisitor($this)); // TODO test
        $traverser->traverse($nodeList);

        // After setting the type in the properties declaration, we can copy it to every property call
        // We need a separate traverse because a property might be set only in the end of the file,
        // after the property is used
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new PropertyFetchTypeInjectorVisitor($this));
        $traverser->traverse($nodeList);

//        $traverser = new NodeTraverser();
        // TODO add AssignmentFromMethodCallTypeInjectorVisitor
//        $traverser->traverse($nodeList);

        $traverser = new NodeTraverser();
        // TODO make a second pass with the PropertyFetchTypeInjectorVisitor
        $traverser->traverse($nodeList);

        $GLOBALS['nodes'] = $nodeList;
    }

    private function addCollections(self ...$nodeCollectionList): void
    {
        $newNodeList = [];
        foreach ($nodeCollectionList as $nodeCollection) {
            $newNodeList[] = $nodeCollection->nodeList;
        }
        $this->nodeList = array_merge($this->nodeList, ...$newNodeList);
    }

    private static function fromSerializedAst(string $serializedAst): self
    {
        $self = new self();

        $self->nodeList = (new JsonDecoder())->decode($serializedAst);

        return $self;
    }

    private function toSerializedAst(bool $prettyPrint = false): string
    {
        $jsonEncoder = JsonEncoder::construct();

        if ($prettyPrint) {
            $jsonEncoder->prettyPrint();
        }

        return $jsonEncoder->encode($this->nodeList);
    }

    private static function parse(string $code): array
    {
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);

        foreach ($parser->parse($code) as $node) {
            if (!$node instanceof Namespace_) {
                continue;
            }
            $namespaceNode = $node;
            $namespace = $namespaceNode->name->toCodeString();
            $className = self::getUnitName($namespaceNode);

            return [$namespace . '\\' . $className => $namespaceNode];
        }

        return [];
    }

    private static function getUnitName(Namespace_ $namespaceNode): string
    {
        return self::getNamespaceUnitNode($namespaceNode)->name->toString();
    }

    /**
     * @return Class_|Interface_|Trait_
     */
    private static function getNamespaceUnitNode(Namespace_ $namespaceNode): Node
    {
        foreach ($namespaceNode->stmts as $stmt) {
            if (
                $stmt instanceof Class_
                || $stmt instanceof Interface_
                || $stmt instanceof Trait_
            ) {
                return $stmt;
            }
        }
        throw new UnitNotFoundInNamespaceException(
            'Could not find a class in the namespace ' . $namespaceNode->name->toCodeString()
        );
    }
}
