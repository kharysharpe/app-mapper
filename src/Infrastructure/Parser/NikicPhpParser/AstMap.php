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

use Closure;
use Hgraca\AppMapper\Core\Port\Parser\AstMapInterface;
use Hgraca\AppMapper\Core\Port\Parser\Node\AdapterNodeCollection;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Node\NodeAdapterFactory;
use PhpParser\Node;
use function array_values;

final class AstMap implements AstMapInterface
{
    /**
     * @var NodeCollection[]
     */
    private $componentNodeCollectionList;

    /**
     * @var NodeCollection
     */
    private $completeNodeCollection;

    /**
     * @var QueryBuilder
     */
    private $queryBuilder;

    /**
     * @var bool
     */
    private $hasTypeInformation = false;

    /**
     * @var NodeAdapterFactory
     */
    private $nodeAdapterFactory;

    /**
     * @var NodeFinder
     */
    private $nodeFinder;

    private function __construct()
    {
        $this->nodeAdapterFactory = new NodeAdapterFactory();
        $this->nodeFinder = new NodeFinder();
    }

    public static function constructFromNodeCollectionList(NodeCollection ...$nodeCollectionList): self
    {
        $self = new self();

        foreach ($nodeCollectionList as $nodeCollection) {
            $self->componentNodeCollectionList[$nodeCollection->getName()] = $nodeCollection;
        }
        $self->completeNodeCollection = NodeCollection::constructFromNodeCollectionList(
            ...array_values($self->componentNodeCollectionList)
        );
        $self->queryBuilder = new QueryBuilder();

        return $self;
    }

    public function serializeToFile(string $filePath, bool $prettyPrint = false): void
    {
        $this->completeNodeCollection->serializeToFile($filePath, $prettyPrint);
    }

    public function findClassesWithFqcnMatchingRegex(
        string $fqcnRegex,
        string $componentName = ''
    ): AdapterNodeCollection {
        $query = $this->queryBuilder->create()
            ->selectComponent($componentName)
            ->selectClassesWithFqcnMatchingRegex($fqcnRegex)
            ->build();

        return $this->query($query);
    }

    public function findClassesCallingMethod(
        string $methodClassFqcnRegex,
        string $methodNameRegex,
        string $componentName = ''
    ): AdapterNodeCollection {
        $query = $this->queryBuilder->create()
            ->selectComponent($componentName)
            ->selectClassesCallingMethod($methodClassFqcnRegex, $methodNameRegex)
            ->build();

        return $this->query($query);
    }

    private function query(Query $query): AdapterNodeCollection
    {
        $this->addTypesToAstCollection();

        $nodeList = array_values(
            $query->getComponentFilter()
                ? $this->getComponentAstCollection($query->getComponentFilter())->toArray()
                : $this->completeNodeCollection->toArray()
        );

        $parserNodeList = $query->shouldReturnSingleResult()
            ? [$this->nodeFinder->findFirst($this->createFilter($query), ...$nodeList)]
            : $this->nodeFinder->find($this->createFilter($query), ...$nodeList);

        return $this->mapNodeList($parserNodeList);
    }

    private function mapNodeList(array $parserNodeList): AdapterNodeCollection
    {
        $nodeList = [];
        foreach ($parserNodeList as $parserNode) {
            $nodeList[] = $this->nodeAdapterFactory->constructFromNode($parserNode);
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

    private function getComponentAstCollection(string $componentName): NodeCollection
    {
        return $this->componentNodeCollectionList[$componentName];
    }

    private function addTypesToAstCollection(): void
    {
        if ($this->hasTypeInformation) {
            return;
        }
        $this->completeNodeCollection->enhance();
        $this->completeNodeCollection->resolveAllTypes();
        $this->hasTypeInformation = true;
    }
}
