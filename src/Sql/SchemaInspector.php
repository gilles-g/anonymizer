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

interface SchemaInspector
{
    public function hasTable(TableName $table): bool;

    public function hasColumn(TableName $table, ColumnName $column): bool;
}
