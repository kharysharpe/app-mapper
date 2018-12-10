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

namespace Hgraca\ContextMapper\Test\StubProjectSrc\Core\Component\X\Domain;

use DateTime;

final class AaaEntity
{
    use AaaTrait;

    /**
     * @var BbbEntity
     */
    private $bbbEntity;

    /**
     * @var DateTime
     */
    private $createdAt;

    /**
     * @var DateTime
     */
    private $aaaProperty; // TODO this is not inferred

    /**
     * @var BbbEntity
     */
    private $bbbProperty; // TODO this is not inferred

    /**
     * @throws \Exception
     */
    public function __construct(BbbEntity $bbbEntity)
    {
        $this->bbbEntity = $bbbEntity;
        $this->createdAt = new DateTime();
        $this->updatedAt = new DateTime(); // TODO updatedAt type should be set in the trait where it is declared
    }

    /**
     * @throws \Exception
     */
    public function methodXxx(): void
    {
        $this->aaaProperty = $this->bbbEntity->methodXxx();
        $this->bbbProperty = BbbEntity::namedConstructor(); // TODO the bbbProperty is not inferred
    }

    public function methodYyy(): BbbEntity
    {
        $aaa = new BbbEntity();

        return BbbEntity::namedConstructor();
    }
}
