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

final readonly class AnonymizationStatement
{
    /**
     * @param array<string, SqlExpression>|null $assignments
     */
    private function __construct(
        private ?TableName $table,
        private ?array $assignments,
        private ?PoolJoin $join,
        private ?SqlExpression $filter,
        private ?SqlExpression $raw,
    ) {
    }

    /**
     * @param array<string, SqlExpression> $assignments
     */
    public static function update(TableName $table, array $assignments, ?PoolJoin $join = null, ?SqlExpression $filter = null): self
    {
        if ([] === $assignments) {
            throw new \InvalidArgumentException('An UPDATE statement needs at least one assignment.');
        }

        return new self($table, $assignments, $join, $filter, null);
    }

    public static function raw(SqlExpression $statement): self
    {
        return new self(null, null, null, null, $statement);
    }

    public function render(Dialect $dialect): RenderedSql
    {
        if (null !== $this->raw) {
            return $this->raw->render($dialect);
        }

        return $this->renderUpdate($dialect, bound: true);
    }

    public function renderInlined(Dialect $dialect): string
    {
        if (null !== $this->raw) {
            return $this->raw->renderInlined($dialect);
        }

        return $this->renderUpdate($dialect, bound: false)->sql;
    }

    public function hasPoolJoin(): bool
    {
        return null !== $this->join;
    }

    private function renderUpdate(Dialect $dialect, bool $bound): RenderedSql
    {
        \assert(null !== $this->table && null !== $this->assignments);

        [$setClause, $bindings] = $this->renderSetClause($dialect, $bound);
        $table = $dialect->quoteIdentifier($this->table);

        if (null === $this->join) {
            $where = $this->renderFilter($dialect, prefix: 'WHERE');

            return RenderedSql::of(trim("UPDATE {$table} SET {$setClause}{$where}"), $bindings);
        }

        $poolAlias = $dialect->quoteIdentifier(TableName::of($this->join->alias->value));
        $poolKey = $poolAlias.'.'.$dialect->quoteIdentifier(ColumnName::of('n'));
        $localKey = $table.'.'.$dialect->quoteIdentifier($this->join->localColumn);
        $condition = "{$poolKey} = {$localKey} % {$this->join->poolSize}";

        if (UpdateJoinStyle::JoinOnUpdate === $dialect->updateJoinStyle()) {
            $where = $this->renderFilter($dialect, prefix: 'WHERE');
            $sql = "UPDATE {$table} JOIN {$poolAlias} ON {$condition} SET {$setClause}{$where}";

            return RenderedSql::of(trim($sql), $bindings);
        }

        $filter = $this->renderFilter($dialect, prefix: 'AND');
        $sql = "UPDATE {$table} SET {$setClause} FROM {$poolAlias} WHERE {$condition}{$filter}";

        return RenderedSql::of(trim($sql), $bindings);
    }

    /**
     * @return array{0: string, 1: array<string, scalar|null>}
     */
    private function renderSetClause(Dialect $dialect, bool $bound): array
    {
        \assert(null !== $this->assignments);

        $clauses = [];
        $bindings = [];

        foreach ($this->assignments as $column => $expression) {
            $columnName = $dialect->quoteIdentifier(ColumnName::of($column));

            if (!$bound) {
                $clauses[] = "{$columnName} = {$expression->renderInlined($dialect)}";

                continue;
            }

            $rendered = $expression->render($dialect);
            $sql = $rendered->sql;

            foreach ($rendered->bindings as $name => $value) {
                $namespaced = $column.'_'.$name;
                $sql = preg_replace('/:'.preg_quote($name, '/').'\b/', ':'.$namespaced, $sql) ?? $sql;
                $bindings[$namespaced] = $value;
            }

            $clauses[] = "{$columnName} = {$sql}";
        }

        return [implode(', ', $clauses), $bindings];
    }

    private function renderFilter(Dialect $dialect, string $prefix): string
    {
        if (null === $this->filter) {
            return '';
        }

        return " {$prefix} {$this->filter->renderInlined($dialect)}";
    }
}
