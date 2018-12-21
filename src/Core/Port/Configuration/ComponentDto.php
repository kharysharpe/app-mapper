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

namespace Hgraca\AppMapper\Core\Port\Configuration;

use function is_dir;

final class ComponentDto
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $path;

    /**
     * @var int
     */
    private $locationX;

    /**
     * @var int
     */
    private $locationY;

    public function __construct(string $name, string $path, int $locationX, int $locationY)
    {
        $this->name = $name;
        $this->path = $path;
        $this->locationX = $locationX;
        $this->locationY = $locationY;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function getLocationX(): int
    {
        return $this->locationX;
    }

    public function getLocationY(): int
    {
        return $this->locationY;
    }

    public function isDir(): bool
    {
        return is_dir($this->getPath());
    }
}
