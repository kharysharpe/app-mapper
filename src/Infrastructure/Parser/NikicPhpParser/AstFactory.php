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

use Hgraca\ContextMapper\Core\Port\Parser\AstFactoryInterface;
use Hgraca\ContextMapper\Core\Port\Parser\AstInterface;

final class AstFactory implements AstFactoryInterface
{
    public function constructFromFolder(string $folder): AstInterface
    {
        return Ast::constructFromFolder($folder);
    }

    public function constructFromFile(string $filePath): AstInterface
    {
        return Ast::constructFromFile($filePath);
    }
}