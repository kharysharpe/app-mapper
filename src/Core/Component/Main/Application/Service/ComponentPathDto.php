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

namespace Hgraca\ContextMapper\Core\Component\Main\Application\Service;

use function is_dir;

final class ComponentPathDto
{
    /**
     * @var string
     */
    private $componentName;

    /**
     * @var string
     */
    private $path;

    public function __construct(string $componentName, string $path)
    {
        $this->componentName = $componentName;
        $this->path = $path;
    }

    public function getComponentName(): string
    {
        return $this->componentName;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function isDir(): bool
    {
        return is_dir($this->getPath());
    }
}
