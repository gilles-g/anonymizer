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
use DbAnonymizer\Plan\TableName;

interface Dialect
{
    public function quoteIdentifier(TableName|ColumnName $identifier): string;

    public function quoteLiteral(string|int|float|bool|null $value): string;

    public function updateJoinStyle(): UpdateJoinStyle;
}
