<?php

declare(strict_types=1);

/*
 * This file is part of my Symfony boilerplate,
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

namespace Acme\App\Infrastructure\Persistence\Session;

use Acme\App\Core\Port\Persistence\KeyValueStorageInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @author Kasper Agg
 * @author Herberto Graca <herberto.graca@gmail.com>
 */
final class KeyValueStorage implements KeyValueStorageInterface
{
    /** @var SessionInterface */
    private $session;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    public function get(string $namespace, string $key): ?string
    {
        return $this->session->get($this->createKey($namespace, $key));
    }

    public function set(string $namespace, string $key, string $value): void
    {
        $this->session->set($this->createKey($namespace, $key), $value);
    }

    public function has(string $namespace, string $key): bool
    {
        return $this->session->has($this->createKey($namespace, $key));
    }

    public function remove(string $namespace, string $key): void
    {
        $this->session->remove($this->createKey($namespace, $key));
    }

    private function createKey(string $namespace, string $key): string
    {
        return sprintf('%s_%s', $namespace, $key);
    }
}
