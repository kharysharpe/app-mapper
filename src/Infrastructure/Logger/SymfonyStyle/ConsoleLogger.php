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

namespace Hgraca\ContextMapper\Infrastructure\Logger\SymfonyStyle;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use function json_encode;

final class ConsoleLogger implements LoggerInterface
{
    /**
     * @var SymfonyStyle
     */
    private $io;

    public function __construct(SymfonyStyle $io)
    {
        $this->io = $io;
    }

    public function emergency($message, array $context = []): void
    {
        if (!$this->shouldLog()) {
            return;
        }
        $this->io->error($this->writeMessage($message, $context));
    }

    public function alert($message, array $context = []): void
    {
        if (!$this->shouldLog()) {
            return;
        }
        $this->io->error($this->writeMessage($message, $context));
    }

    public function critical($message, array $context = []): void
    {
        if (!$this->shouldLog()) {
            return;
        }
        $this->io->error($this->writeMessage($message, $context));
    }

    public function error($message, array $context = []): void
    {
        if (!$this->shouldLog()) {
            return;
        }
        $this->io->error($this->writeMessage($message, $context));
    }

    public function warning($message, array $context = []): void
    {
        if (!$this->shouldLog()) {
            return;
        }
        $this->io->caution($this->writeMessage($message, $context));
    }

    public function notice($message, array $context = []): void
    {
        if (!$this->shouldLog()) {
            return;
        }
        $this->io->caution($this->writeMessage($message, $context));
    }

    public function info($message, array $context = []): void
    {
        if (!$this->shouldLog()) {
            return;
        }
        $this->io->success($this->writeMessage($message, $context));
    }

    public function debug($message, array $context = []): void
    {
        if (!$this->shouldLog()) {
            return;
        }
        $this->io->comment($this->writeMessage($message, $context));
    }

    public function log($level, $message, array $context = []): void
    {
        if (!$this->shouldLog()) {
            return;
        }
    }

    private function shouldLog(): bool
    {
        return $this->io->isVerbose();
    }

    private function writeMessage(string $message, array $context): string
    {
        return $message . "\nContext:\n" . json_encode($context);
    }
}
