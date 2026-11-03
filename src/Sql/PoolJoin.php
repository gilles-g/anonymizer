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

use DbAnonymizer\Plan\ColumnName;
use DbAnonymizer\Pool\PoolAlias;

final readonly class PoolJoin
{
    private function __construct(
        public PoolAlias $alias,
        public ColumnName $localColumn,
        public int $poolSize,
    ) {
    }

    public static function of(PoolAlias $alias, ColumnName $localColumn, int $poolSize): self
    {
        if ($poolSize < 1) {
            throw new \InvalidArgumentException(\sprintf('A pool size must be at least 1, %d given.', $poolSize));
        }

        return new self($alias, $localColumn, $poolSize);
    }
}
