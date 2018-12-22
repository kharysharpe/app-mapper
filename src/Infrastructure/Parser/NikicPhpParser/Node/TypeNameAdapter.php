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

use Hgraca\AppMapper\Core\Port\Parser\Node\TypeNodeInterface;
use Hgraca\AppMapper\Core\SharedKernel\Exception\NotImplementedException;
use Hgraca\PhpExtension\String\ClassHelper;
use PhpParser\Node;
use PhpParser\Node\Name;
use PhpParser\Node\Name\FullyQualified;

final class TypeNameAdapter implements TypeNodeInterface
{
    /**
     * @var Name
     */
    private $name;

    public function __construct(Name $name)
    {
        $this->name = $name;
    }

    public function getFullyQualifiedType(): string
    {
        /** @var FullyQualified $resolvedName */
        $resolvedName = $this->name->getAttribute('resolvedName');

        return ltrim($resolvedName->toCodeString(), '\\');
    }

    public function getCanonicalType(): string
    {
        return ClassHelper::extractCanonicalClassName($this->getFullyQualifiedType());
    }

    public function getTypeNode(): Node
    {
        return $this->name->getAttribute('astCollection');
    }

    public function getAllFamilyFullyQualifiedNameList(): array
    {
        throw new NotImplementedException();
    }

    public function __toString(): string
    {
        return ClassHelper::extractCanonicalClassName(__CLASS__) . ' - ' . $this->name;
    }
}
