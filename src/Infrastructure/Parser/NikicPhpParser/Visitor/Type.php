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

namespace Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Visitor;

use Hgraca\PhpExtension\String\ClassService;
use PhpParser\Node;

final class Type
{
    /**
     * @var string
     */
    private $fqcn;

    /**
     * @var null|Node
     */
    private $ast;

    public function __construct(string $fqcn, ?Node $ast)
    {
        $this->fqcn = $fqcn;
        $this->ast = $ast;
    }

    public static function getName(): string
    {
        return ClassService::extractCanonicalClassName(__CLASS__);
    }

    public function getFqcn(): string
    {
        return $this->fqcn;
    }

    public function getAst(): Node
    {
        return $this->ast;
    }

    public function hasAst(): bool
    {
        return $this->ast !== null;
    }
}
