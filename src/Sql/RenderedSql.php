<?php

declare(strict_types=1);

/*
 * This file is part of the gilles-g/db-anonymizer package.
 *
 * (c) gilles-g <ggauthier@spiriit.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DbAnonymizer\Sql;

final readonly class RenderedSql
{
    /**
     * @param array<string, scalar|null> $bindings
     */
    private function __construct(
        public string $sql,
        public array $bindings,
    ) {
    }

    /**
     * @param array<string, scalar|null> $bindings
     */
    public static function of(string $sql, array $bindings = []): self
    {
        return new self($sql, $bindings);
    }
}
