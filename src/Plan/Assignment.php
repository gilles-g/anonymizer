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

namespace DbAnonymizer\Plan;

use DbAnonymizer\Sql\SqlExpression;

final readonly class Assignment
{
    private function __construct(
        public ColumnName $column,
        public SqlExpression $value,
    ) {
    }

    public static function of(ColumnName $column, SqlExpression $value): self
    {
        return new self($column, $value);
    }
}
