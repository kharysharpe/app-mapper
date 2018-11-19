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
use Hgraca\ContextMapper\Core\Component\Main\Application\Service\ComponentPathDto;
use Hgraca\ContextMapper\Core\Component\Main\Application\Service\ContextMapService;
use Hgraca\ContextMapper\Presentation\Console\AbstractCommandStopwatchDecorator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class GenerateCommand extends AbstractCommandStopwatchDecorator
{
    private const DELAY_OPEN = 2.0;

    private const NAME = 'cmap:map:generate';
    private const OPT_FOLDER = 'folder';
    private const OPT_FILE = 'file';
    private const OPT_OUT_FILE = 'outFile';
    private const OPT_OPEN_OUT_FILE = 'openOutFile';
    private const OPT_COMPONENT_NAMES = 'componentNames';
    private const OPT_TITLE = 'title';
    private const OPT_TITLE_SIZE = 'titleSize';
    private const OPT_USE_CASE_FQCN_REGEX = 'useCaseFqcnRegex';
    private const OPT_SUBSCRIBER_FQCN_REGEX = 'subscriberFqcnRegex';

    /**
     * To make your command lazily loaded, configure the $defaultName static property,
     * so it will be instantiated only when the command is actually called.
     *
     * @var string
     */
    protected static $defaultName = self::NAME;

    /**
     * @var ContextMapService
     */
    private $contextMapService;

    public function __construct(ContextMapService $contextMapService)
    {
        parent::__construct();
        $this->contextMapService = $contextMapService;
    }

    protected function configure(): void
    {
        $this->setDescription('Create a full report about use cases.')
            ->addOption(
                self::OPT_FOLDER,
                'o',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'The folder where to scan for PHP files.'
            )
            ->addOption(
                self::OPT_FILE,
                'i',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'The file with the AST to load into memory.'
            )
            ->addOption(
                self::OPT_COMPONENT_NAMES,
                'c',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'The component names, in the same order as they are passed with options -o and -i.'
            )
            ->addOption(
                self::OPT_OUT_FILE,
                'u',
                InputOption::VALUE_OPTIONAL,
                'The file path for the image.',
                sys_get_temp_dir() . \DIRECTORY_SEPARATOR . 'image.cmap.png'
            )
            ->addOption(
                self::OPT_OPEN_OUT_FILE,
                'p',
                InputOption::VALUE_NONE,
                'Should open the generated image.'
            )
            ->addOption(
                self::OPT_TITLE,
                't',
                InputOption::VALUE_OPTIONAL,
                'The context map title.',
                'Context Map'
            )
            ->addOption(
                self::OPT_TITLE_SIZE,
                'z',
                InputOption::VALUE_OPTIONAL,
                'The context map title font size.',
                '30'
            )
            ->addOption(
                self::OPT_USE_CASE_FQCN_REGEX,
                'm',
                InputOption::VALUE_OPTIONAL,
                'The use case class FQCN regex.',
                '/.*Command$/'
            )
            ->addOption(
                self::OPT_SUBSCRIBER_FQCN_REGEX,
                'b',
                InputOption::VALUE_OPTIONAL,
                'The subscriber class FQCN regex.',
                '/.*Subscriber$/'
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
        if (
            $input->getOption(self::OPT_FOLDER) === null
            && $input->getOption(self::OPT_FILE) === null
        ) {
            $this->io->error('You need to either provide a folder to scan or a file with the AST.');
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
        $this->contextMapService->printContextMap(
            $this->contextMapService->createFromPaths(
                $input->getOption(self::OPT_TITLE),
                $input->getOption(self::OPT_USE_CASE_FQCN_REGEX),
                $input->getOption(self::OPT_SUBSCRIBER_FQCN_REGEX),
                ...$this->getComponentPathList($input)
            ),
            $input->getOption(self::OPT_OUT_FILE),
            $input->getOption(self::OPT_TITLE_SIZE)
        );

        if ($input->getOption(self::OPT_OPEN_OUT_FILE)) {
            $this->display($input->getOption(self::OPT_OUT_FILE));
        }
    }

    public function display(string $filePath): void
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

    private function getComponentNameFromPath(string $path): string
    {
        return mb_substr($path, mb_strrpos($path, \DIRECTORY_SEPARATOR) + 1);
    }

    /**
     * @return ComponentPathDto[]
     */
    private function getComponentPathList(InputInterface $input): array
    {
        $componentNameList = $input->getOption(self::OPT_COMPONENT_NAMES);
        $pathList = array_merge($input->getOption(self::OPT_FOLDER), $input->getOption(self::OPT_FILE));
        $componentCounter = 0;
        $componentPathList = [];
        foreach ($pathList as $path) {
            $componentName = (string) ($componentNameList[$componentCounter] ?? $this->getComponentNameFromPath($path));
            $componentPathList[] = new ComponentPathDto($componentName, $path);
            ++$componentCounter;
        }

        return $componentPathList;
    }
}
