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
use Hgraca\ContextMapper\Core\Port\Parser\AstInterface;
use Hgraca\ContextMapper\Core\Port\Parser\Exception\ParserException;
use Hgraca\ContextMapper\Core\Port\Parser\NodeCollection;
use Hgraca\ContextMapper\Core\Port\Parser\NodeCollectionInterface;
use Hgraca\ContextMapper\Core\Port\Parser\NodeInterface;
use Hgraca\ContextMapper\Core\Port\Parser\QueryInterface;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Node\UseCaseNode;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Visitor\ParentConnectorVisitor;
use Hgraca\PhpExtension\String\JsonEncoder;
use PhpParser\JsonDecoder;
use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\FindingVisitor;
use PhpParser\NodeVisitor\FirstFindingVisitor;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use function array_merge;
use function is_array;

final class Ast implements AstInterface
{
    /** @var Stmt[] */
    private $itemList = [];

    private function __construct()
    {
    }

    public static function constructFromFolder(string $folder): AstInterface
    {
        $parser = (new ParserFactory())->create(ParserFactory::PREFER_PHP7);

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($folder));
        $files = new \RegexIterator($files, '/\.php$/');
        $nodeList = [];
        foreach ($files as $file) {
            $code = file_get_contents($file->getPathName());
            $nodeList[] = $parser->parse($code);
        }

        $ast = new self();
        $ast->itemList = array_merge(...$nodeList);

        return $ast;
    }

    public static function constructFromFile(string $filePath): AstInterface
    {
        return self::fromSerializedAst(file_get_contents($filePath));
    }

    public static function fromSerializedAst(string $serializedAst): AstInterface
    {
        $ast = new self();

        $ast->itemList = (new JsonDecoder())->decode($serializedAst);

        return $ast;
    }

    public function toSerializedAst(bool $prettyPrint = false): string
    {
        $jsonEncoder = JsonEncoder::construct();

        if ($prettyPrint) {
            $jsonEncoder->prettyPrint();
        }

        return $jsonEncoder->encode($this->itemList);
    }

    public function query(QueryInterface $query): NodeCollectionInterface
    {
        $parserNodeList = $query->shouldReturnSingleResult()
            ? [$this->findFirst($this->createFilter($query), ...$this->itemList)]
            : $this->find($this->createFilter($query), ...$this->itemList);

        return $this->mapNodeList($parserNodeList);
    }

    private function mapNodeList(array $parserNodeList): NodeCollectionInterface
    {
        $nodeList = [];
        foreach ($parserNodeList as $parserNode) {
            $nodeList[] = $this->mapNode($parserNode);
        }

        return new NodeCollection(...$nodeList);
    }

    private function mapNode(Node $parserNode): NodeInterface
    {
        switch (true) {
            case $parserNode instanceof Class_:
                return UseCaseNode::constructFromClass($parserNode);
            default:
                throw new ParserException();
        }
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
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new ParentConnectorVisitor());
        $traverser->addVisitor(new NameResolver(null, ['preserveOriginalNames' => true, 'replaceNodes' => false]));
        $visitor = new FindingVisitor($filter);
        $traverser->addVisitor($visitor);
        $traverser->traverse($nodes);

        return $visitor->getFoundNodes();
    }

    private function findFirst(callable $filter, Node ...$nodes): ?Node
    {
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new ParentConnectorVisitor());
        $traverser->addVisitor(new NameResolver(null, ['preserveOriginalNames' => true, 'replaceNodes' => false]));
        $visitor = new FirstFindingVisitor($filter);
        $traverser->addVisitor($visitor);
        $traverser->traverse($nodes);

        return $visitor->getFoundNode();
    }
}