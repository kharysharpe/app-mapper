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
use Hgraca\ContextMapper\Core\Component\Main\Application\Service\AstService;
use Hgraca\ContextMapper\Presentation\Console\AbstractCommandStopwatchDecorator;
use PhpParser\Error;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function getcwd;

class BuildAstFileCommand extends AbstractCommandStopwatchDecorator
{
    private const NAME = 'cmap:astCollection:build';
    private const ARG_ROOT_PATH = 'rootPath';
    private const ARG_FILE_PATH = 'filePath';
    private const OPT_PRETTY_PRINT = 'prettyPrint';
    private const DEFAULT_FILE_NAME = 'cmap.astCollection.json';

    /**
     * To make your command lazily loaded, configure the $defaultName static property,
     * so it will be instantiated only when the command is actually called.
     *
     * @var string
     */
    protected static $defaultName = self::NAME;

    /**
     * @var AstService
     */
    private $astService;

    public function __construct(AstService $astService)
    {
        parent::__construct();
        $this->astService = $astService;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Creates a reusable AST file from a tree of PHP files.')
            ->addArgument(self::ARG_ROOT_PATH, InputArgument::REQUIRED, 'The root folder to look for PHP files.')
            ->addArgument(
                self::ARG_FILE_PATH,
                InputArgument::OPTIONAL,
                'The file where to put the serialized AST.',
                getcwd() . '/' . self::DEFAULT_FILE_NAME
            )
            ->addOption(
                self::OPT_PRETTY_PRINT,
                'p',
                InputOption::VALUE_NONE,
                'If used, the output JSON will be pretty printed.'
            )
            ->setHelp($this->getCommandHelp());
    }

    /**
     * @throws Exception
     */
    protected function executeUseCase(InputInterface $input, OutputInterface $output): void
    {
        try {
            $this->astService->createAstFileFromFolder(
                $input->getArgument(self::ARG_ROOT_PATH),
                $input->getArgument(self::ARG_FILE_PATH),
                $input->getOption(self::OPT_PRETTY_PRINT)
            );
        } catch (Error $e) {
            $this->io->warning('Parsing error: ' . $e->getMessage());
        }
    }

    private function getCommandHelp(): string
    {
        return <<<'HELP'
The <info>%command.name%</info> creates a reusable AST file from a tree of PHP files:

  <info>php %command.full_name%</info>
HELP;
    }
}
