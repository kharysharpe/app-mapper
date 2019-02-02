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

use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeCollection;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeDecoratorAccessorTrait;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\AbstractNodeDecorator;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\DefaultNodeDecorator;
use Hgraca\PhpExtension\String\ClassHelper;
use Hgraca\PhpExtension\String\StringHelper;
use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;
use function class_exists;
use function count;
use function get_class;

class DecoratorVisitor extends NodeVisitorAbstract
{
    use NodeDecoratorAccessorTrait;

    /**
     * @var AbstractNodeDecorator[]
     */
    private $stack;

    /**
     * @var NodeCollection
     */
    private $nodeCollection;

    public function __construct(NodeCollection $nodeCollection)
    {
        $this->nodeCollection = $nodeCollection;
    }

    public function beforeTraverse(array $nodes): void
    {
        $this->stack = [];
    }

    public function enterNode(Node $node): void
    {
        $decoratorNode = $this->createDecoratorFor($node);
        $this->setNodeDecorator($node, $decoratorNode);
        $this->stack[] = $decoratorNode;
    }

    public function leaveNode(Node $node): void
    {
        array_pop($this->stack);
    }

    private function createDecoratorFor(Node $node): AbstractNodeDecorator
    {
        $parent = !empty($this->stack) ? $this->stack[count($this->stack) - 1] : null;

        $decoratorClassName = $this->getDecoratorNamespace() . $this->getDecoratorCanonicalName($node);

        if (class_exists($decoratorClassName)) {
            return new $decoratorClassName($node, $parent, $this->nodeCollection);
        }

        return new DefaultNodeDecorator($node, $parent);
    }

    private function getDecoratorNamespace(): string
    {
        return StringHelper::removeFromEnd(
            ClassHelper::extractCanonicalClassName(AbstractNodeDecorator::class),
            AbstractNodeDecorator::class
        );
    }

    private function getDecoratorCanonicalName(Node $node): string
    {
        return rtrim(ClassHelper::extractCanonicalClassName(get_class($node)), '_') . 'NodeDecorator';
    }
}
