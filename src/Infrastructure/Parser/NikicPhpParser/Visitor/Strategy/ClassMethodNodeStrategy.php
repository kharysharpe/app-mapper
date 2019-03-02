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

namespace Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Strategy;

use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeDecoratorAccessorTrait;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\ClassMethodNodeDecorator;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeNodeCollector;
use PhpParser\Node;
use PhpParser\Node\Stmt\ClassMethod;

final class ClassMethodNodeStrategy extends AbstractStrategy
{
    use NodeDecoratorAccessorTrait;

    private $variableCollector;

    /**
     * @var TypeNodeCollector
     */
    private $propertyCollector;

    public function __construct(TypeNodeCollector $variableCollector, TypeNodeCollector $propertyCollector)
    {
        $this->variableCollector = $variableCollector;
        $this->propertyCollector = $propertyCollector;
    }

    /**
     * @param Node|ClassMethod $classMethod
     */
    public function enterNode(Node $classMethod): void
    {
        $this->validateNode($classMethod);
        /** @var ClassMethodNodeDecorator $classMethodDecorator */
        $classMethodDecorator = $this->getNodeDecorator($classMethod);

        if ($classMethodDecorator->isWithinClass() || $classMethodDecorator->isWithinTrait()) {
            $this->propertyCollector->initializeWith(
                $classMethodDecorator->getEnclosingClassLikeNode()->getDeclaredProperties()
            );
        }
    }

    /**
     * @param Node|ClassMethod $classMethod
     */
    public function leaveNode(Node $classMethod): void
    {
        $this->validateNode($classMethod);

        $this->variableCollector->reset();
    }

    public static function getNodeTypeHandled(): string
    {
        return ClassMethod::class;
    }
}
