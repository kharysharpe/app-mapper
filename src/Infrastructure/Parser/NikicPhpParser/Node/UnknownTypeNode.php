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

use Hgraca\ContextMapper\Core\Port\Parser\Node\TypeNodeInterface;
use PhpParser\Node;
use function get_class;
use function is_string;

final class UnknownTypeNode implements TypeNodeInterface
{
    /**
     * @var string
     */
    private $phpParserNodeType = '';

    /**
     * @param null|string|Node $node
     */
    public function __construct($node = null)
    {
        switch (true) {
            case $node === null:
                $this->phpParserNodeType = 'null';
                break;
            case is_string($node):
                $this->phpParserNodeType = $node;
                break;
            default:
                $this->phpParserNodeType = get_class($node);
                break;
        }
    }

    public function getFullyQualifiedType(): string
    {
        return 'Unknown (' . $this->phpParserNodeType . ')';
    }

    public function getCanonicalType(): string
    {
        return 'Unknown (' . $this->phpParserNodeType . ')';
    }
}
