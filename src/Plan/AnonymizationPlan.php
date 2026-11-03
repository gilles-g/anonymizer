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

use DbAnonymizer\Pool\PoolAlias;
use DbAnonymizer\Pool\PoolColumn;
use DbAnonymizer\Pool\Seed;
use DbAnonymizer\Sql\AnonymizationStatement;
use DbAnonymizer\Sql\SqlExpression;

final readonly class AnonymizationPlan
{
    /**
     * @param list<TableAnonymization> $tables
     */
    public function __construct(
        private PoolAlias $pool,
        private Seed $seed,
        private int $poolSize,
        private array $tables,
    ) {
        if ([] === $tables) {
            throw InvalidPlan::because('An anonymization plan needs at least one table.');
        }
    }

    /**
     * @return list<TableAnonymization>
     */
    public function tables(): array
    {
        return $this->tables;
    }

    /**
     * @return iterable<AnonymizationStatement>
     */
    public function statements(): iterable
    {
        $poolColumns = $this->deduplicatedPoolColumns();

        if ([] !== $poolColumns) {
            yield $this->poolSetupStatement($poolColumns);
        }

        foreach ($this->tables as $table) {
            yield $table->statement($this->pool, $this->seed, $this->poolSize);
        }

        if ([] !== $poolColumns) {
            yield AnonymizationStatement::raw(SqlExpression::of(
                'DROP TABLE IF EXISTS {{pool}}',
                ['pool' => TableName::of($this->pool->value)],
            ));
        }
    }

    /**
     * @return list<PoolColumn>
     */
    private function deduplicatedPoolColumns(): array
    {
        $unique = [];

        foreach ($this->tables as $table) {
            foreach ($table->poolColumns() as $column) {
                $unique[$column->formatter.'|'.$column->column->value.'|'.$column->offset] = $column;
            }
        }

        return array_values($unique);
    }

    /**
     * @param non-empty-list<PoolColumn> $poolColumns
     */
    private function poolSetupStatement(array $poolColumns): AnonymizationStatement
    {
        $identifiers = [
            'pool' => TableName::of($this->pool->value),
            'n' => ColumnName::of('n'),
        ];
        $definitions = ['{{n}} INT PRIMARY KEY'];

        foreach ($poolColumns as $index => $column) {
            $placeholder = 'col'.$index;
            $identifiers[$placeholder] = $column->column;
            $definitions[] = '{{'.$placeholder.'}} VARCHAR(255)';
        }

        $template = 'CREATE TEMPORARY TABLE {{pool}} ('.implode(', ', $definitions).')';

        return AnonymizationStatement::raw(SqlExpression::of($template, $identifiers));
    }
}
