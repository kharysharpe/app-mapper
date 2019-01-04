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

namespace Hgraca\AppMapper\Presentation\Console;

use Hgraca\AppMapper\Core\Port\Logger\StaticLoggerFacade;
use Hgraca\AppMapper\Infrastructure\Logger\SymfonyStyle\ConsoleLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;
use function class_exists;
use const PHP_EOL;

abstract class AbstractCommandStopwatchDecorator extends Command
{
    /**
     * @var SymfonyStyle
     */
    protected $io;

    abstract protected function executeUseCase(InputInterface $input, OutputInterface $output): void;

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
        StaticLoggerFacade::setLogger(new ConsoleLogger($this->io));
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        if ($output->isVerbose() && class_exists('\Symfony\Component\Stopwatch\Stopwatch')) {
            $stopwatch = new Stopwatch();
            $stopwatch->start(static::class);
        }

        $this->executeUseCase($input, $output);

        if (isset($stopwatch)) {
            $event = $stopwatch->stop(static::class);
            $this->io->comment(
                sprintf(
                    'Elapsed time: %.2f ms' . PHP_EOL
                    . 'Consumed memory: %.2f MB',
                    $event->getDuration(),
                    $event->getMemory() / (1024 ** 2)
                )
            );
        }
    }
}
