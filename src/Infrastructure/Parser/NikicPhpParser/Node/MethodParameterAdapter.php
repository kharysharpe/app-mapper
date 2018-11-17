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

namespace Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Node;

use Hgraca\ContextMapper\Core\Port\Parser\Node\MethodParameterInterface;
use Hgraca\ContextMapper\Core\Port\Parser\Node\TypeNodeInterface;
use PhpParser\Node\Param;

final class MethodParameterAdapter implements TypeNodeInterface, MethodParameterInterface
{
    /**
     * @var TypeNameAdapter
     */
    private $parameterType;

    public function __construct(Param $parameter)
    {
        $this->parameterType = new TypeNameAdapter($parameter->type);
    }

    public function getFullyQualifiedType(): string
    {
        return $this->parameterType->getFullyQualifiedType();
    }

    public function getCanonicalType(): string
    {
        return $this->parameterType->getCanonicalType();
    }
}
