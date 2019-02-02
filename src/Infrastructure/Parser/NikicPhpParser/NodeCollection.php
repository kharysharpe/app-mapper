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

namespace Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser;

use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\AstNodeNotFoundException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\UnitNotFoundInNamespaceException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\DecoratorVisitor;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeResolverInjectorVisitor;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeResolverVisitor;
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
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver(null, ['preserveOriginalNames' => true, 'replaceNodes' => false]));
        $traverser->addVisitor(new DecoratorVisitor($this));
        $traverser->addVisitor(new TypeResolverInjectorVisitor());
        $traverser->traverse(array_values($this->nodeList));
    }

    public function resolveAllTypes(): void
    {
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new TypeResolverVisitor());
        $traverser->traverse(array_values($this->nodeList));
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
