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

namespace DbAnonymizer\Mask;

use DbAnonymizer\Plan\ColumnName;
use DbAnonymizer\Plan\TableName;
use DbAnonymizer\Pool\PoolAlias;
use DbAnonymizer\Pool\Seed;

final readonly class MaskingContext
{
    public function __construct(
        public TableName $table,
        public ColumnName $column,
        public ColumnName $identity,
        public Seed $seed,
        public PoolAlias $pool,
    ) {
    }
}
