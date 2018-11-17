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
use Hgraca\ContextMapper\Core\Component\Main\Application\Query\DispatchedEventQuery;
use Hgraca\ContextMapper\Core\Port\Parser\AstFactoryInterface;
use Hgraca\ContextMapper\Presentation\Console\AbstractCommandStopwatchDecorator;
use PhpParser\Error;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function count;

class DispatchedEventsCommand extends AbstractCommandStopwatchDecorator
{
    private const NAME = 'cmap:map:events-dispatched';
    private const OPT_FOLDER = 'folder';
    private const OPT_FILE = 'file';

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
    private $astFactory;

    /**
     * @var DispatchedEventQuery
     */
    private $dispatchedEventQuery;

    public function __construct(AstFactoryInterface $astFactory, DispatchedEventQuery $dispatchedEventQuery)
    {
        parent::__construct();
        $this->astFactory = $astFactory;
        $this->dispatchedEventQuery = $dispatchedEventQuery;
    }

    protected function configure(): void
    {
        $this->setDescription('Create a full report about triggered events.')
            ->addOption(
                self::OPT_FOLDER,
                'o',
                InputOption::VALUE_OPTIONAL,
                'The folder where to scan for PHP files.'
            )
            ->addOption(
                self::OPT_FILE,
                'i',
                InputOption::VALUE_OPTIONAL,
                'The file with the AST to load into memory.'
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
            $ast = ($input->getOption(self::OPT_FOLDER) !== null)
                ? $this->astFactory->constructFromFolder($input->getOption(self::OPT_FOLDER))
                : $this->astFactory->constructFromFile($input->getOption(self::OPT_FILE));

            $map = $this->dispatchedEventQuery->queryAst($ast);
            $map = $this->sort('Dispatcher FQCN', $map);
            $this->io->title('EVENTS DISPATCHED PER BOUNDED CONTEXT');
            $this->io->section('Bounded context: ');
            if (count($map) !== 0) {
                $this->io->table(array_keys($map[0]), $map);
            }
            $this->io->comment('Dispatched events count: ' . count($map));
        } catch (Error $e) {
            $this->io->warning('Parsing error: ' . $e->getMessage());
        }
    }

    private function sort(string $columnName, $data): array
    {
        $column = array_column($data, $columnName);
        array_multisort($column, SORT_ASC, $data);

        return $data;
    }
}
