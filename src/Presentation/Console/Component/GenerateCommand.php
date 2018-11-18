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
use Hgraca\ContextMapper\Core\Component\Main\Application\Query\ListenerQuery;
use Hgraca\ContextMapper\Core\Component\Main\Application\Query\SubscriberQuery;
use Hgraca\ContextMapper\Core\Component\Main\Application\Query\UseCaseQuery;
use Hgraca\ContextMapper\Core\Component\Main\Domain\Component;
use Hgraca\ContextMapper\Core\Component\Main\Domain\ContextMap;
use Hgraca\ContextMapper\Core\Port\Parser\AstFactoryInterface;
use Hgraca\ContextMapper\Core\Port\Printer\PrinterInterface;
use Hgraca\ContextMapper\Presentation\Console\AbstractCommandStopwatchDecorator;
use PhpParser\Error;
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

    /**
     * To make your command lazily loaded, configure the $defaultName static property,
     * so it will be instantiated only when the command is actually called.
     *
     * @var string
     */
    protected static $defaultName = self::NAME;

    /**
     * @var AstFactoryInterface
     */
    private $astMapFactory;

    /**
     * @var UseCaseQuery
     */
    private $useCaseQuery;

    /**
     * @var ListenerQuery
     */
    private $listenerQuery;

    /**
     * @var SubscriberQuery
     */
    private $subscriberQuery;

    /**
     * @var PrinterInterface
     */
    private $printer;

    public function __construct(
        PrinterInterface $printer,
        AstFactoryInterface $astMapFactory,
        UseCaseQuery $useCaseQuery,
        ListenerQuery $listenerQuery,
        SubscriberQuery $subscriberQuery
    ) {
        parent::__construct();
        $this->printer = $printer;
        $this->astMapFactory = $astMapFactory;
        $this->useCaseQuery = $useCaseQuery;
        $this->listenerQuery = $listenerQuery;
        $this->subscriberQuery = $subscriberQuery;
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
        try {
            $componentList = [];
            $componentNameList = $input->getOption(self::OPT_COMPONENT_NAMES);
            $componentCounter = 0;
            foreach ($input->getOption(self::OPT_FOLDER) as $folderKey => $folderPath) {
                $astMap = $this->astMapFactory->constructFromFolder($folderPath);
                $componentName = (string) ($componentNameList[$componentCounter++] ?? $componentCounter++);
                $componentList[$componentName] = $astMap;
            }
            foreach ($input->getOption(self::OPT_FILE) as $fileKey => $astFilePath) {
                $astMap = $this->astMapFactory->constructFromFile($astFilePath);
                $componentName = (string) ($componentNameList[$componentCounter++] ?? $componentCounter++);
                $componentList[$componentName] = $astMap;
            }
            foreach ($componentList as $componentName => $componentAstMap) {
                $componentList[] = new Component(
                    $componentName,
                    $this->useCaseQuery->queryAst($componentAstMap),
                    $this->listenerQuery->queryAst($componentAstMap),
                    $this->subscriberQuery->queryAst($componentAstMap)
                );
            }

            $contextMap = new ContextMap($input->getOption(self::OPT_TITLE), ...$componentList);

            file_put_contents(
                $input->getOption(self::OPT_OUT_FILE),
                $this->printer->printToImage($contextMap)
            );

            if ($input->getOption(self::OPT_OPEN_OUT_FILE)) {
                $this->display($input->getOption(self::OPT_OUT_FILE));
            }
        } catch (Error $e) {
            $this->io->warning('Parsing error: ' . $e->getMessage());
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
}
