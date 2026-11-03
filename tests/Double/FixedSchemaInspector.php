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

namespace DbAnonymizer\Tests\Double;

use DbAnonymizer\Plan\ColumnName;
use DbAnonymizer\Plan\TableName;
use DbAnonymizer\Sql\SchemaInspector;

final readonly class FixedSchemaInspector implements SchemaInspector
{
    /**
     * @param list<string>                $tables
     * @param array<string, list<string>> $columns
     */
    private function __construct(
        private array $tables,
        private array $columns,
    ) {
    }

    /**
     * @param list<string>                $tables
     * @param array<string, list<string>> $columns
     */
    public static function of(array $tables, array $columns = []): self
    {
        return new self($tables, $columns);
    }

    public function hasTable(TableName $table): bool
    {
        return \in_array($table->value, $this->tables, true);
    }

    public function hasColumn(TableName $table, ColumnName $column): bool
    {
        return \in_array($column->value, $this->columns[$table->value] ?? [], true);
    }
}
