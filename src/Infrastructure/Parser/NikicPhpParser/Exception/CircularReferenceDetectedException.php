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

use Hgraca\AppMapper\Core\SharedKernel\Exception\AppMapperRuntimeException;
use PhpParser\Node;

final class CircularReferenceDetectedException extends AppMapperRuntimeException
{
    public function __construct(Node $node, string $fqcn)
    {
        $relevantInfo = [];
        $loopNode = $node;
        while ($loopNode->hasAttribute('parentNode')) {
            $relevantInfo[] = get_class($loopNode) . ' => '
                . (property_exists($loopNode, 'name')
                    ? $loopNode->name
                    : 'no_name'
                );
            $loopNode = $loopNode->getAttribute('parentNode');
        }

        $message = "Circular reference detected when adding type '$fqcn' to collection in node:\n"
            . json_encode($relevantInfo, JSON_PRETTY_PRINT);

        parent::__construct($message);
    }
}
