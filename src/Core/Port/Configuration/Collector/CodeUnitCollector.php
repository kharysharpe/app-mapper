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

namespace Hgraca\AppMapper\Core\Port\Configuration\Collector;

use Hgraca\AppMapper\Core\Port\Configuration\Exception\ConfigurationException;
use InvalidArgumentException;
use function array_key_exists;
use function array_keys;

class CodeUnitCollector
{
    public const CRITERIA_CLASS_FQCN = 'classFqcn';
    public const CRITERIA_METHOD_NAME = 'methodName';

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

        foreach ($collector as $index => $criteria) {
            if (!isset($criteria['type'])) {
                throw new InvalidArgumentException('Collector needs a type.');
            }
            switch (true) {
                case $criteria['type'] === self::CRITERIA_CLASS_FQCN
                    && array_key_exists('regex', $criteria):
                    $criteriaInstance = new ClassFqcnRegexCriteria($criteria['regex']);
                    break;
                case $criteria['type'] === self::CRITERIA_METHOD_NAME
                    && array_key_exists('regex', $criteria):
                    $criteriaInstance = new MethodNameRegexCriteria($criteria['regex']);
                    break;
                default:
                    $criteriaType = $criteria['type'];
                    throw new ConfigurationException(
                        "Unknown or incomplete criteria with index '$index' and type '$criteriaType'"
                    );
            }
            $self->criteriaList[$criteria['type']] = $criteriaInstance;
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

    public function getCriteriaByType(string $type): CriteriaInterface
    {
        if (!isset($this->criteriaList[$type])) {
            throw new InvalidArgumentException(
                "This collector does not have the criteria type '$type'. Existing types are: "
                . implode(', ', array_keys($this->criteriaList))
            );
        }

        return $this->criteriaList[$type];
    }
}
