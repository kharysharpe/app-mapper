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

namespace Hgraca\ContextMapper\Presentation\Console\Component;

use Exception;
use Hgraca\ContextMapper\Core\Component\Main\Application\Service\ContextMapService;
use Hgraca\ContextMapper\Core\Port\Configuration\ConfigurationFactoryInterface;
use Hgraca\ContextMapper\Presentation\Console\AbstractCommandStopwatchDecorator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function file_exists;
use function is_file;

class GenerateCommand extends AbstractCommandStopwatchDecorator
{
    private const DELAY_OPEN = 2.0;

    private const NAME = 'cmap:map:generate';
    private const OPT_CONFIG_FILE = 'configFilePath';
    private const OPT_OUT_FILE = 'outFile';
    private const OPT_OPEN_OUT_FILE = 'openOutFile';

    /**
     * To make your command lazily loaded, configure the $defaultName static property,
     * so it will be instantiated only when the command is actually called.
     *
     * @var string
     */
    protected static $defaultName = self::NAME;

    /**
     * @var ConfigurationFactoryInterface
     */
    private $configurationFactory;

    /**
     * @var ContextMapService
     */
    private $contextMapService;

    public function __construct(
        ConfigurationFactoryInterface $configurationFactory,
        ContextMapService $contextMapService
    ) {
        parent::__construct();
        $this->configurationFactory = $configurationFactory;
        $this->contextMapService = $contextMapService;
    }

    protected function configure(): void
    {
        $this->setDescription('Create a full report about use cases.')
            ->addOption(
                self::OPT_CONFIG_FILE,
                'f',
                InputOption::VALUE_OPTIONAL,
                'The configuration file absolute or relative path.',
                '.cmap.yml'
            )
            ->addOption(
                self::OPT_OUT_FILE,
                'i',
                InputOption::VALUE_OPTIONAL,
                'The file path for the image.',
                'var/cmap.png'
            )
            ->addOption(
                self::OPT_OPEN_OUT_FILE,
                'o',
                InputOption::VALUE_NONE,
                'Should open the generated image.'
            );
    }

    /**
     * This method is executed after initialize() and before execute(). Its purpose
     * is to check if some of the options/arguments are missing and interactively
     * ask the user for those values.
     *
     * This method is completely optional. If you are developing an internal console
     * command, you probably should not implement this method because it requires
     * quite a lot of work. However, if the command is meant to be used by external
     * users, this method is a nice way to fall back and prevent errors.
     */
    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        $configFilePath = $input->getOption(self::OPT_CONFIG_FILE);
        if (!file_exists($configFilePath) || !is_file($configFilePath)) {
            $this->io->error('Configuration file not found: ' . $configFilePath);
            exit(1);
        }
    }

    /**
     * This method is executed after interact() and initialize(). It usually
     * contains the logic to execute to complete this command task.
     *
     * @throws Exception
     */
    protected function executeUseCase(InputInterface $input, OutputInterface $output): void
    {
        $this->io->comment('Started...');

        $this->io->comment('Creating config...');
        $config = $this->configurationFactory->createConfig($input->getOption(self::OPT_CONFIG_FILE));

        $this->io->comment('Creating context map...');
        $contextMap = $this->contextMapService->createFromConfig($config);

        $this->io->comment('Printing context map...');
        $this->contextMapService->printContextMap($contextMap, $config);

        if ($input->getOption(self::OPT_OPEN_OUT_FILE)) {
            $this->io->comment('Displaying context map...');
            $this->display($config->getOutputFileAbsPath());
        }

        $this->io->comment('Done!');
    }

    private function display(string $filePath): void
    {
        static $next = 0;

        switch (true) {
            case $this->isOsWindows():
                // open image in untitled, temporary background shell
                exec('start "" ' . escapeshellarg($filePath) . ' >NUL');
                break;
            case $this->isOsMac():
                // open image in background (redirect stdout to /dev/null, sterr to stdout and run in background)
                exec('open ' . escapeshellarg($filePath) . ' > /dev/null 2>&1 &');
                break;
            default:
                if ($next > microtime(true)) {
                    // wait some time between calling xdg-open because earlier calls will be ignored otherwise
                    sleep(self::DELAY_OPEN);
                }
                // open image in background (redirect stdout to /dev/null, sterr to stdout and run in background)
                exec('xdg-open ' . escapeshellarg($filePath) . ' > /dev/null 2>&1 &');
        }

        $next = microtime(true) + self::DELAY_OPEN;
    }

    private function isOsWindows(): bool
    {
        return mb_stripos(PHP_OS, 'WIN') === 0;
    }

    private function isOsMac(): bool
    {
        return mb_strtoupper(PHP_OS) === 'DARWIN';
    }
}
