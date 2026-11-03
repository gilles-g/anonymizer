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
use DbAnonymizer\Pool\PoolAlias;
use DbAnonymizer\Pool\PoolColumn;

final readonly class SqlExpression
{
    /**
     * @param array<string, ColumnName|TableName> $identifiers
     * @param array<string, scalar|null>          $bindings
     */
    private function __construct(
        private string $template,
        private array $identifiers,
        private array $bindings,
    ) {
    }

    public static function literal(string|int|float|bool|null $value): self
    {
        return new self(':literal', [], ['literal' => $value]);
    }

    public static function column(ColumnName $column): self
    {
        return new self('{{column}}', ['column' => $column], []);
    }

    public static function poolColumn(PoolAlias $pool, PoolColumn $column): self
    {
        return new self('{{alias}}.{{column}}', [
            'alias' => TableName::of($pool->value),
            'column' => $column->column,
        ], []);
    }

    /**
     * @param array<string, ColumnName|TableName> $identifiers
     * @param array<string, scalar|null>          $bindings
     */
    public static function of(string $template, array $identifiers = [], array $bindings = []): self
    {
        return new self($template, $identifiers, $bindings);
    }

    public function render(Dialect $dialect): RenderedSql
    {
        return RenderedSql::of($this->substituteIdentifiers($this->template, $dialect), $this->bindings);
    }

    public function renderInlined(Dialect $dialect): string
    {
        $sql = $this->substituteIdentifiers($this->template, $dialect);

        foreach ($this->bindings as $name => $value) {
            $sql = preg_replace('/:'.preg_quote($name, '/').'\b/', $dialect->quoteLiteral($value), $sql) ?? $sql;
        }

        return $sql;
    }

    /**
     * @return array<string, scalar|null>
     */
    public function bindings(): array
    {
        return $this->bindings;
    }

    private function substituteIdentifiers(string $sql, Dialect $dialect): string
    {
        foreach ($this->identifiers as $name => $identifier) {
            $sql = str_replace('{{'.$name.'}}', $dialect->quoteIdentifier($identifier), $sql);
        }

        return $sql;
    }
}
