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

namespace Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Node;

use Hgraca\AppMapper\Core\Port\Parser\Node\MethodParameterInterface;
use Hgraca\AppMapper\Core\Port\Parser\Node\TypeNodeInterface;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\NotImplementedException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\ParamNodeDecorator;
use Hgraca\PhpExtension\String\ClassHelper;

final class MethodParameterAdapter implements TypeNodeInterface, MethodParameterInterface
{
    /**
     * @var ParamNodeDecorator
     */
    private $parameterNodeDecorator;

    public function __construct(ParamNodeDecorator $parameterNodeDecorator)
    {
        $this->parameterNodeDecorator = $parameterNodeDecorator;
    }

    public function getFullyQualifiedType(): string
    {
        return $this->parameterNodeDecorator->getDeclaredType()->getTypeCollection()->getUniqueType()->getFqn();
    }

    public function getCanonicalType(): string
    {
        return ClassHelper::extractCanonicalClassName($this->getFullyQualifiedType());
    }

    public function getAllFamilyFullyQualifiedNameList(): array
    {
        throw new NotImplementedException();
    }
}
