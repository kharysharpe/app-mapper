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

namespace Hgraca\ContextMapper\Infrastructure\Configuration\Symfony;

use Hgraca\ContextMapper\Core\Port\Configuration\Configuration;
use Hgraca\ContextMapper\Core\Port\Configuration\ConfigurationFactoryInterface;
use Hgraca\ContextMapper\Core\Port\Configuration\Exception\ConfigurationException;
use Hgraca\ContextMapper\Infrastructure\Configuration\Symfony\Yaml\Loader;
use Hgraca\PhpExtension\String\StringService;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class ConfigurationFactory implements ConfigurationFactoryInterface
{
    private const FILE_TYPE_YML = 'yml';
    private const FILE_TYPE_YAML = 'yaml';

    private $loaderStrategyList = [];

    public function __construct()
    {
        $this->loaderStrategyList[self::FILE_TYPE_YML] = new Loader();
    }

    public function createConfig(string $fileAbsPath): Configuration
    {
        $options = (new OptionsResolver())
            ->setRequired(['components'])
            ->setDefault('paths', ['./src'])
            ->setDefault('include_files', ['type' => 'filePath', 'regex' => '.*\.php$'])
            ->setDefault('out_file', './var/cmap.svg')
            ->setDefault('title', ['text' => 'Context Map', 'font_size' => 30])
            ->setDefault('legend', ['position' => ['x' => 90, 'y' => 90]])
            ->setDefault(
                'code_units',
                [
                    'component' => [
                        'color' => 'Lavender',
                    ],
                    'use_case' => [
                        'collector' => [
                            [
                                'type' => 'classFqcn',
                                'regex' => '.*Command$',
                            ],
                        ],
                        'color' => 'Lightsalmon',
                    ],
                    'listener' => [
                        'collector' => [
                            [
                                'type' => 'classFqcn',
                                'regex' => '.*Listener$',
                            ],
                        ],
                        'color' => 'Honeydew',
                    ],
                    'subscriber' => [
                        'collector' => [
                            [
                                'type' => 'classFqcn',
                                'regex' => '.*Subscriber$',
                            ],
                        ],
                        'color' => 'Lightcyan',
                    ],
                    'event' => [
                        'collector' => [
                            [
                                'type' => 'classFqcn',
                                'regex' => '.*EventDispatcherInterface$',
                            ],
                            [
                                'type' => 'classFqcn',
                                'regex' => '^dispatch$',
                            ],
                        ],
                        'color' => 'Grey',
                        'line' => 'dashed',
                    ],
                ]
            )
            ->addAllowedTypes('components', 'array')
            ->resolve($this->resolveLoader($fileAbsPath)->load($fileAbsPath));

        return new Configuration(
            $options['paths'],
            $options['include_files'],
            $options['out_file'],
            $options['title'],
            $options['legend'],
            $options['code_units'],
            $options['components']
        );
    }

    private function resolveLoader(string $fileAbsPath): Loader
    {
        switch (true) {
            case StringService::hasEnding(self::FILE_TYPE_YAML, $fileAbsPath):
            case StringService::hasEnding(self::FILE_TYPE_YML, $fileAbsPath):
                return $this->loaderStrategyList[self::FILE_TYPE_YML];
            default:
                throw new ConfigurationException('Configuration file type unknown: ' . $fileAbsPath);
        }
    }
}
