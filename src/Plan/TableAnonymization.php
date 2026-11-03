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

use DbAnonymizer\Mask\MaskingContext;
use DbAnonymizer\Mask\PoolDependent;
use DbAnonymizer\Pool\PoolAlias;
use DbAnonymizer\Pool\PoolColumn;
use DbAnonymizer\Pool\Seed;
use DbAnonymizer\Sql\AnonymizationStatement;
use DbAnonymizer\Sql\PoolJoin;

final readonly class TableAnonymization
{
    /**
     * @param list<ColumnRule> $rules
     */
    public function __construct(
        private TableName $table,
        private ColumnName $identity,
        private ColumnName $poolKey,
        private RowFilter $filter,
        private array $rules,
    ) {
        if ([] === $rules) {
            throw InvalidPlan::because(\sprintf('Table "%s" needs at least one column rule.', $table->value));
        }
    }

    public function table(): TableName
    {
        return $this->table;
    }

    /**
     * @return iterable<ColumnName>
     */
    public function columns(): iterable
    {
        yield $this->identity;
        yield $this->poolKey;

        foreach ($this->rules as $rule) {
            yield $rule->column();
        }
    }

    /**
     * @return iterable<PoolColumn>
     */
    public function poolColumns(): iterable
    {
        foreach ($this->rules as $rule) {
            if ($rule->mask() instanceof PoolDependent) {
                yield from $rule->mask()->poolColumns();
            }
        }
    }

    public function usesPool(): bool
    {
        foreach ($this->rules as $rule) {
            if ($rule->mask() instanceof PoolDependent) {
                return true;
            }
        }

        return false;
    }

    public function statement(PoolAlias $pool, Seed $seed, int $poolSize): AnonymizationStatement
    {
        $assignments = [];

        foreach ($this->rules as $rule) {
            $context = new MaskingContext($this->table, $rule->column(), $this->identity, $seed, $pool);
            $assignment = $rule->assignment($context);
            $assignments[$assignment->column->value] = $assignment->value;
        }

        $join = $this->usesPool() ? PoolJoin::of($pool, $this->poolKey, $poolSize) : null;

        return AnonymizationStatement::update($this->table, $assignments, $join, $this->filter->toSqlExpression());
    }
}
