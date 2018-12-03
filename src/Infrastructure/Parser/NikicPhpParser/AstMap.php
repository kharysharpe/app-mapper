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

use Closure;
use Hgraca\ContextMapper\Core\Port\Parser\AstMapInterface;
use Hgraca\ContextMapper\Core\Port\Parser\Node\AdapterNodeCollection;
use Hgraca\ContextMapper\Core\Port\Parser\QueryInterface;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Exception\AstNodeNotFoundException;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Exception\UnitNotFoundInNamespaceException;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Node\NodeFactory;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Visitor\InstantiationTypeInjectorVisitor;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Visitor\MethodReturnTypeInjectorVisitor;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Visitor\ParentConnectorVisitor;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Visitor\PropertyDeclarationTypeInjectorVisitor;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Visitor\PropertyTypeInjectorVisitor;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Visitor\StaticCallClassTypeInjectorVisitor;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Visitor\VariableTypeInjectorVisitor;
use Hgraca\PhpExtension\String\JsonEncoder;
use PhpParser\JsonDecoder;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\FindingVisitor;
use PhpParser\NodeVisitor\FirstFindingVisitor;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use function array_key_exists;
use function array_merge;
use function array_values;

final class AstMap implements AstMapInterface
{
    /** @var Namespace_[] */
    private $itemList = [];

    /** @var bool */
    private $isAstMapEnhanced = false;

    private function __construct()
    {
    }

    public static function constructFromFolder(string $folder): AstMapInterface
    {
        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folder));
        $files = new \RegexIterator($files, '/\.php$/');
        $nodeList = [];
        foreach ($files as $file) {
            $nodeList[] = self::parse(file_get_contents($file->getPathName()));
        }

        $ast = new self();
        $ast->itemList = array_merge(...$nodeList);

        return $ast;
    }

    public static function unserializeFromFile(string $filePath): AstMapInterface
    {
        return self::fromSerializedAst(file_get_contents($filePath));
    }

    public function serializeToFile(string $filePath, bool $prettyPrint = false): void
    {
        file_put_contents($filePath, $this->toSerializedAst($prettyPrint));
    }

    public static function constructFromAstMapList(self ...$astMapList): AstMapInterface
    {
        $itemListList = [];
        foreach ($astMapList as $astMap) {
            $itemListList[] = $astMap->itemList;
        }
        $completeAstMap = new self();
        $completeAstMap->itemList = array_merge(...$itemListList);

        return $completeAstMap;
    }

    public function query(QueryInterface $query): AdapterNodeCollection
    {
        $itemList = array_values($this->itemList);

        $parserNodeList = $query->shouldReturnSingleResult()
            ? [$this->findFirst($this->createFilter($query), ...$itemList)]
            : $this->find($this->createFilter($query), ...$itemList);

        return $this->mapNodeList($parserNodeList);
    }

    public function hasAstNode(string $fqcn): bool
    {
        $key = trim($fqcn, '\\');
        if (!array_key_exists($key, $this->itemList)) {
            return false;
        }

        return true;
    }

    public function getAstNode(string $fqcn): Node
    {
        $key = trim($fqcn, '\\');
        if (!array_key_exists($key, $this->itemList)) {
            throw new AstNodeNotFoundException($key);
        }

        return self::getNamespaceUnitNode($this->itemList[$key]);
    }

    private static function fromSerializedAst(string $serializedAst): AstMapInterface
    {
        $ast = new self();

        $ast->itemList = (new JsonDecoder())->decode($serializedAst);

        return $ast;
    }

    private function toSerializedAst(bool $prettyPrint = false): string
    {
        $jsonEncoder = JsonEncoder::construct();

        if ($prettyPrint) {
            $jsonEncoder->prettyPrint();
        }

        return $jsonEncoder->encode($this->itemList);
    }

    private function mapNodeList(array $parserNodeList): AdapterNodeCollection
    {
        $nodeList = [];
        foreach ($parserNodeList as $parserNode) {
            $nodeList[] = NodeFactory::constructNodeAdapter($parserNode);
        }

        return new AdapterNodeCollection(...$nodeList);
    }

    private function createFilter(QueryInterface $query): Closure
    {
        return function (Node $node) use ($query) {
            foreach ($query->getFilterList() as $filter) {
                if (!$filter($node)) {
                    return false;
                }
            }

            return true;
        };
    }

    private function find(callable $filter, Node ...$nodes): array
    {
        $this->isAstMapEnhanced ?: $this->enhanceAst();
        $traverser = new NodeTraverser();
        $visitor = new FindingVisitor($filter);
        $traverser->addVisitor($visitor);
        $traverser->traverse($nodes);

        return $visitor->getFoundNodes();
    }

    private function findFirst(callable $filter, Node ...$nodes): ?Node
    {
        $this->isAstMapEnhanced ?: $this->enhanceAst();
        $traverser = new NodeTraverser();
        $visitor = new FirstFindingVisitor($filter);
        $traverser->addVisitor($visitor);
        $traverser->traverse($nodes);

        return $visitor->getFoundNode();
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

    private function enhanceAst(): void
    {
        $nodeList = array_values($this->itemList);

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new ParentConnectorVisitor());
        $traverser->addVisitor(new NameResolver(null, ['preserveOriginalNames' => true, 'replaceNodes' => false]));
        $traverser->addVisitor(new StaticCallClassTypeInjectorVisitor($this));
        $traverser->addVisitor(new MethodReturnTypeInjectorVisitor($this));
        $traverser->addVisitor(new InstantiationTypeInjectorVisitor($this));
        $traverser->addVisitor(new VariableTypeInjectorVisitor($this));
        $traverser->traverse($nodeList);

        // First we finish the previous swipe to set all variables
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new PropertyDeclarationTypeInjectorVisitor($this));
        $traverser->traverse($nodeList);

        // After setting the type in the properties declaration, we can copy it to every property call
        // We need a separate traverse because a property might be set only in the end of the file,
        // after the property is used
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new PropertyTypeInjectorVisitor($this));
        $traverser->traverse($nodeList);

        $this->isAstMapEnhanced = true;
    }
}
