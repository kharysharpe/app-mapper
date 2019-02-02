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

namespace Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception;

use Hgraca\AppMapper\Core\Port\Parser\Exception\ParserException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\NodeDecorator\AbstractNodeDecorator;

final class ParentNodeNotFoundException extends ParserException
{
    public function __construct(string $type, AbstractNodeDecorator $nodeDecorator)
    {
        $class = get_class($nodeDecorator);
        $msg = "Could not find node of type '$type' as parent of '$class': \n"
            . $nodeDecorator->resolveNodeTreeAsJson();

        parent::__construct($msg);
    }
}
