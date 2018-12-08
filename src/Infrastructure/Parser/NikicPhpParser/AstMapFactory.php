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

use Hgraca\ContextMapper\Core\Port\Configuration\ComponentDto;
use Hgraca\ContextMapper\Core\Port\Parser\AstMapFactoryInterface;
use Hgraca\ContextMapper\Core\Port\Parser\AstMapInterface;

final class AstMapFactory implements AstMapFactoryInterface
{
    public function constructFromPath(string $path): AstMapInterface
    {
        return AstMap::constructFromNodeCollectionList(
            is_dir($path)
                ? NodeCollection::constructFromFolder($path)
                : NodeCollection::unserializeFromFile($path)
        );
    }

    public function constructFromComponentDtoList(ComponentDto ...$componentDtoList): AstMapInterface
    {
        $componentNodeCollectionList = [];
        foreach ($componentDtoList as $componentDto) {
            $componentNodeCollectionList[] = is_dir($componentDto->getPath())
                ? NodeCollection::constructFromFolder($componentDto->getPath(), $componentDto->getName())
                : NodeCollection::unserializeFromFile($componentDto->getPath(), $componentDto->getName());
        }

        return AstMap::constructFromNodeCollectionList(...$componentNodeCollectionList);
    }
}
