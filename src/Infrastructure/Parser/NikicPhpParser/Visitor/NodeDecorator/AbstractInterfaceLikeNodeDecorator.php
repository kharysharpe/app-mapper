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

namespace Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator;

use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\MethodNotFoundInClassException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Type;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeCollection;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Trait_;

/**
 * @property Class_|Trait_ $node
 */
abstract class AbstractInterfaceLikeNodeDecorator extends AbstractNodeDecorator
{
    public function resolveTypeCollection(): TypeCollection
    {
        $fqcn = implode('\\', $this->node->namespacedName->parts);

        return new TypeCollection(new Type($fqcn, $this));
    }

    /**
     * @return ClassMethodNodeDecorator[]
     */
    public function getMethods(): array
    {
        $useList = array_filter(
            $this->node->stmts,
            function (Stmt $stmt) {
                return $stmt instanceof ClassMethod;
            }
        );

        $result = [];
        foreach ($useList as $use) {
            $result[] = $this->getNodeDecorator($use);
        }

        return $result;
    }

    public function getMethod(string $methodName): ClassMethodNodeDecorator
    {
        foreach ($this->getMethods() as $methodDecorator) {
            if ($methodDecorator->getName() === $methodName) {
                return $methodDecorator;
            }
        }

        throw MethodNotFoundInClassException::constructFromFqcn(
            $methodName,
            $this->getTypeCollection()->getUniqueType()->getFqn()
        );
    }

    public function hasMethod(string $methodName): bool
    {
        foreach ($this->getMethods() as $methodDecorator) {
            if ($methodDecorator->getName() === $methodName) {
                return true;
            }
        }

        return false;
    }
}
