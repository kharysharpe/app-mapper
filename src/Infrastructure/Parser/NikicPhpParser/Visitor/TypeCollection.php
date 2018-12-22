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
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\CircularReferenceDetectedException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\EmptyCollectionException;
use Hgraca\AppMapper\Infrastructure\Parser\NikicPhpParser\Exception\NonUniqueTypeCollectionException;
use Hgraca\PhpExtension\Collection\Collection;
use Hgraca\PhpExtension\String\ClassHelper;
use PhpParser\Node;
use function uniqid;

/**
 * @property Type[] $itemList
 */
final class TypeCollection extends Collection
{
    private const REPEATED_TYPE_ADD_LIMIT = 1000;

    /**
     * @var Node
     */
    private $node;

    /**
     * @var int[]
     */
    private $repeatedTypeAddition = [];

    /**
     * @var string
     */
    private $id;

    public function __construct(?Node $node = null, Type ...$itemList)
    {
        parent::__construct($itemList);
        $this->node = $node;
        $this->id = uniqid('', false);
    }

    public static function getName(): string
    {
        return ClassHelper::extractCanonicalClassName(__CLASS__);
    }

    public function addType(Type $item): void
    {
        if (
            isset($this->repeatedTypeAddition[$item->getFcqn()])
            && $this->repeatedTypeAddition[$item->getFcqn()] >= 100
        ) {
            $count = $this->repeatedTypeAddition[$item->getFcqn()] + 1;
            StaticLoggerFacade::notice(
                "Adding type '{$item->getFcqn()}' to collection {$this->id} with size {$this->count()} for the {$count}th time\n"
            );
        }
        $this->itemList[$item->getFcqn()] = $item;
        $this->repeatedTypeAddition[$item->getFcqn()] = isset($this->repeatedTypeAddition[$item->getFcqn()])
            ? $this->repeatedTypeAddition[$item->getFcqn()] + 1
            : 1;
        if ($this->repeatedTypeAddition[$item->getFcqn()] >= self::REPEATED_TYPE_ADD_LIMIT) {
            $relevantInfo = [];
            $loopNode = $this->node;
            while ($loopNode->hasAttribute('parentNode')) {
                $relevantInfo[] = get_class($loopNode) . ' => '
                    . (property_exists($loopNode, 'name')
                        ? $loopNode->name
                        : 'no_name'
                    );
                $loopNode = $loopNode->getAttribute('parentNode');
            }
            throw new CircularReferenceDetectedException(
                "Circular reference detected when adding type '{$item->getFcqn()}' to collection in node:\n"
                . json_encode($relevantInfo, JSON_PRETTY_PRINT)
            );
        }
    }

    public function addTypeCollection(self $newTypeCollection): void
    {
        foreach ($newTypeCollection as $type) {
            $this->addType($type);
        }
    }

    /**
     * @return Type[]
     */
    public function toArray(): array
    {
        return $this->itemList;
    }

    public function getUniqueType(): Type
    {
        if ($this->count() > 1) {
            throw new NonUniqueTypeCollectionException($this);
        }

        if ($this->count() === 0) {
            throw new EmptyCollectionException();
        }

        return reset($this->itemList);
    }
}
