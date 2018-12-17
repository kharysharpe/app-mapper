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

namespace Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Visitor;

use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Exception\TypeNotFoundInNodeException;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Exception\UnknownPropertyException;
use PhpParser\Node;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Property;

/**
 * This visitor makes a swipe injecting the types from the property declarations into the property usages
 * (PropertyFetch)
 */
final class PropertyFetchTypeInjectorVisitor extends AbstractTypeInjectorVisitor
{
    use PropertyBufferTrait;

    public function enterNode(Node $node): void
    {
        parent::enterNode($node);
        switch (true) {
            // TODO should follow family and traits up and set their property types in this buffer
            case $node instanceof Property:
                // Properties declared at the top of the class are added to buffer
                try {
                    $this->addPropertyTypeToBuffer((string) $node->props[0]->name, self::getTypeFromNode($node));
                } catch (TypeNotFoundInNodeException $e) {
                    // If the property does not have the type, we ignore it
                }
                break;
            case $node instanceof PropertyFetch:
                // Properties used within the class are injected with type from buffer
                try {
                    $this->addTypeToNode($node, $this->getPropertyTypeFromBuffer((string) $node->name));
                } catch (UnknownPropertyException $e) {
                    // If the buffer does not have the property, we ignore it
                }
                break;
        }
    }

    public function leaveNode(Node $node): void
    {
        if ($node instanceof Class_) {
            $this->resetPropertyTypeBuffer();
        }
    }
}
