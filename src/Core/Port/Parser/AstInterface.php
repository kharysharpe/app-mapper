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

namespace Hgraca\ContextMapper\Core\Port\Parser;

interface AstInterface
{
    public static function constructFromFolder(string $folder): self;

    public static function constructFromFile(string $filePath): self;

    public static function fromSerializedAst(string $serializedAst): self;

    public function toSerializedAst(bool $prettyPrint = false): string;

    public function query(QueryInterface $query): NodeCollectionInterface;
}