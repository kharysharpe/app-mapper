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

use Hgraca\AppMapper\Core\Port\Logger\StaticLoggerFacade;
use Hgraca\AppMapper\Core\Port\Parser\Node\AdapterNodeInterface;
use Hgraca\AppMapper\Core\Port\Parser\Node\MethodArgumentInterface;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\UnresolvableNodeTypeException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeDecoratorAccessorTrait;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\Type;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor\TypeCollection;
use Hgraca\PhpExtension\Collection\Collection;
use PhpParser\Node\Arg;

final class MethodArgumentAdapter extends Collection implements MethodArgumentInterface
{
    use NodeDecoratorAccessorTrait;

    public function __construct(Arg $argument)
    {
        $argumentValueDecorator = $this->getNodeDecorator($argument->value);

        try {
            $this->itemList = array_unique(
                NodeAdapterFactory::constructFromTypeCollection($argumentValueDecorator->getTypeCollection())->toArray()
            );
        } catch (UnresolvableNodeTypeException $e) {
            StaticLoggerFacade::warning(
                "Silently ignoring a UnresolvableNodeTypeException in this adapter.\n"
                . "This should be fixed in the type addition visitors.\n"
                . $e->getMessage(),
                [__METHOD__]
            );
            $this->itemList =
                NodeAdapterFactory::constructFromTypeCollection(
                    new TypeCollection(Type::constructUnknownFromNode($argumentValueDecorator))
                )->toArray();
        }
    }

    /**
     * @return AdapterNodeInterface[]
     */
    public function toArray(): array
    {
        return $this->itemList;
    }
}
