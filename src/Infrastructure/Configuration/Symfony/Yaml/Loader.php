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

namespace Hgraca\AppMapper\Infrastructure\Configuration\Symfony\Yaml;

use Hgraca\AppMapper\Core\Port\Configuration\Exception\MissingFileException;
use Hgraca\AppMapper\Infrastructure\Configuration\Symfony\LoaderInterface;
use Symfony\Component\Yaml\Yaml;

final class Loader implements LoaderInterface
{
    /**
     * @throws MissingFileException
     */
    public function load(string $fileAbsPath): array
    {
        if (!file_exists($fileAbsPath)) {
            throw new MissingFileException("Could not find a configuration file at '$fileAbsPath'");
        }

        return Yaml::parse(file_get_contents($fileAbsPath));
    }
}
