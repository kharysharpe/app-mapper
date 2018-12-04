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
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Node\NodeFactory;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\FindingVisitor;
use PhpParser\NodeVisitor\FirstFindingVisitor;

final class AstMap implements AstMapInterface
{
    /**
     * @var NodeCollection
     */
    private $nodeCollection;

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    private function __construct(NodeCollection $nodeCollection)
    {
        $this->nodeCollection = $nodeCollection;
        $this->queryBuilder = new QueryBuilder();
    }

    public function serializeToFile(string $filePath, bool $prettyPrint = false): void
    {
        $this->nodeCollection->serializeToFile($filePath, $prettyPrint);
    }

    public function findClassesWithFqcnMatchingRegex(string $fqcnRegex): AdapterNodeCollection
    {
        $query = $this->queryBuilder->create()
            ->selectClassesWithFqcnMatchingRegex($fqcnRegex)
            ->build();

        return $this->query($query);
    }

    public function findClassesCallingMethod(
        string $methodClassFqcnRegex,
        string $methodNameRegex
    ): AdapterNodeCollection {
        $query = $this->queryBuilder->create()
            ->selectClassesCallingMethod($methodClassFqcnRegex, $methodNameRegex)
            ->build();

        return $this->query($query);
    }

    public static function constructFromAstMapList(self ...$astMapList): AstMapInterface
    {
        $nodeCollectionList = [];
        foreach ($astMapList as $astMap) {
            $nodeCollectionList[] = $astMap->nodeCollection;
        }

        return self::constructFromNodeCollection(
            NodeCollection::constructFromNodeCollectionList(...$nodeCollectionList)
        );
    }

    public static function constructFromNodeCollection(NodeCollection $nodeCollection): self
    {
        return new self($nodeCollection);
    }

    private function query(Query $query): AdapterNodeCollection
    {
        $itemList = array_values($this->nodeCollection->toArray());

        $parserNodeList = $query->shouldReturnSingleResult()
            ? [$this->findFirst($this->createFilter($query), ...$itemList)]
            : $this->find($this->createFilter($query), ...$itemList);

        return $this->mapNodeList($parserNodeList);
    }

    private function mapNodeList(array $parserNodeList): AdapterNodeCollection
    {
        $nodeList = [];
        foreach ($parserNodeList as $parserNode) {
            $nodeList[] = NodeFactory::constructNodeAdapter($parserNode);
        }

        return new AdapterNodeCollection(...$nodeList);
    }

    private function createFilter(Query $query): Closure
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
        $visitor = new FindingVisitor($filter);
        $traverser->addVisitor($visitor);
        $traverser->traverse($nodes);

        return $visitor->getFoundNodes();
    }

    private function findFirst(callable $filter, Node ...$nodes): ?Node
    {
        $traverser = new NodeTraverser();
        $visitor = new FirstFindingVisitor($filter);
        $traverser->addVisitor($visitor);
        $traverser->traverse($nodes);

        return $visitor->getFoundNode();
    }
}
