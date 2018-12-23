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

namespace Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Visitor;

use Hgraca\AppMapper\Core\Port\Logger\StaticLoggerFacade;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\TypeNotFoundInNodeException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\UnknownPropertyException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\NodeTypeManagerTrait;
use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeVisitorAbstract;

/**
 * This visitor makes a swipe injecting the types from the property declarations into the property usages
 * (PropertyFetch)
 */
final class PropertyFetchTypeInjectorVisitor extends NodeVisitorAbstract
{
    use NodeTypeManagerTrait;
    use PropertyCollectorTrait;

    public function enterNode(Node $node): void
    {
        switch (true) {
            case $node instanceof Property:
                // Properties declared at the top of the class are added to buffer
                try {
                    $this->collectPropertyType(
                        (string) $node->props[0]->name,
                        self::getTypeCollectionFromNode($node)
                    );
                } catch (TypeNotFoundInNodeException $e) {
                    // If the property does not have the type, we ignore it
                }
                break;
            case $node instanceof PropertyFetch:
                // Properties used within the class are injected with type from buffer
                StaticLoggerFacade::notice(
                    "We are only adding types to properties in the class itself.\n"
                    . "We should fix this by adding them also to the super classes and traits.\n",
                    [__METHOD__]
                );
                try {
                    $this->addTypeCollectionToNode($node, $this->getCollectedPropertyType((string) $node->name));
                } catch (UnknownPropertyException $e) {
                    StaticLoggerFacade::warning(
                        "Silently ignoring a UnknownPropertyException in this visitor.\n"
                        . "The property is not in the buffer, so we can't add it to the PropertyFetch.\n"
                        . "This should be fixed in the type addition visitors.\n"
                        . $e->getMessage(),
                        [__METHOD__]
                    );
                }
                break;
        }
    }

    public function leaveNode(Node $node): void
    {
        if ($node instanceof Class_) {
            $this->resetCollectedPropertyType();
        }
    }
}
