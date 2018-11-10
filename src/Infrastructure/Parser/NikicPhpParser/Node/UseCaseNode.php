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

namespace Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Node;

use Hgraca\ContextMapper\Core\Port\Parser\UseCaseNodeInterface;
use Hgraca\ContextMapper\Infrastructure\Parser\NikicPhpParser\Node\Wrapper\ClassWrapper;
use Hgraca\PhpExtension\String\StringService;
use PhpParser\Node\Stmt\Class_;

final class UseCaseNode implements UseCaseNodeInterface
{
    /** @var string */
    private $fqcn;

    /** @var string */
    private $canonicalClassName;

    /** @var string */
    private $name;

    private function __construct()
    {
    }

    public static function constructFromClass(Class_ $class): self
    {
        $classWrapper = new ClassWrapper($class);

        $node = new self();
        $node->fqcn = $classWrapper->getFullyQualifiedClassName();
        $node->canonicalClassName = $classWrapper->getCanonicalClassName();
        $node->name = self::removeLastWord(
            StringService::separateCapitalizedWordsWithSpace($node->canonicalClassName)
        );

        return $node;
    }

    public function toArray(): array
    {
        return [
            'Use Case' => $this->name,
            'Class' => $this->canonicalClassName,
            'FQCN' => $this->fqcn,
        ];
    }

    private static function removeLastWord(string $string): string
    {
        return mb_substr($string, 0, mb_strrpos($string, ' '));
    }
}
