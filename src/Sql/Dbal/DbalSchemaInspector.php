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

namespace DbAnonymizer\Sql\Dbal;

use DbAnonymizer\Plan\ColumnName;
use DbAnonymizer\Plan\TableName;
use DbAnonymizer\Sql\SchemaInspector;
use Doctrine\DBAL\Connection;

final readonly class DbalSchemaInspector implements SchemaInspector
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function hasTable(TableName $table): bool
    {
        return $this->connection->createSchemaManager()->tableExists($table->value);
    }

    public function hasColumn(TableName $table, ColumnName $column): bool
    {
        if (!$this->hasTable($table)) {
            return false;
        }

        foreach ($this->connection->createSchemaManager()->listTableColumns($table->value) as $tableColumn) {
            if ($tableColumn->getName() === $column->value) {
                return true;
            }
        }

        return false;
    }
}
