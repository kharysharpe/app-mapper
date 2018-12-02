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

namespace Hgraca\ContextMapper\Core\Port\Configuration\Collector;

use Hgraca\ContextMapper\Core\Port\Configuration\Exception\ConfigurationException;
use function array_key_exists;

class CodeUnitCollector
{
    /**
     * @var array
     */
    private $criteriaList = [];

    private function __construct()
    {
    }

    public static function constructFromCollector(array $collector): self
    {
        $self = new self();

        foreach ($collector as $criteria) {
            if (!isset($criteria['type'])) {
                throw new \InvalidArgumentException('Collector needs a type.');
            }
            switch (true) {
                case $criteria['type'] === 'classFqcn'
                    && array_key_exists('regex', $criteria):
                    $self->criteriaList[] = new ClassFqcnRegexCriteria($criteria['regex']);
                    break;
                case $criteria['type'] === 'methodName'
                    && array_key_exists('regex', $criteria):
                    $self->criteriaList[] = new MethodNameRegexCriteria($criteria['regex']);
                    break;
                default:
                    $criteriaType = $criteria['type'];
                    throw new ConfigurationException("Unknown criteria type '$criteriaType'");
            }
        }

        return $self;
    }

    public static function constructFromCriteria(CriteriaInterface ...$criteriaList): self
    {
        $self = new self();

        $self->criteriaList = $criteriaList;

        return $self;
    }

    public function hasCriteria(string $fqcn): bool
    {
        foreach ($this->criteriaList as $criteria) {
            if ($criteria instanceof $fqcn) {
                return true;
            }
        }

        return false;
    }

    public function getCriteriaListAsString(): array
    {
        $list = [];
        foreach ($this->criteriaList as $criteria) {
            $list[] = (string) $criteria;
        }

        return $list;
    }
}
