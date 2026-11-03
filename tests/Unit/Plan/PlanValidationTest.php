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

namespace DbAnonymizer\Tests\Unit\Plan;

use DbAnonymizer\Plan\AnonymizationPlan;
use DbAnonymizer\Plan\ColumnName;
use DbAnonymizer\Plan\ColumnRule;
use DbAnonymizer\Plan\PlanValidation;
use DbAnonymizer\Plan\RowFilter;
use DbAnonymizer\Plan\TableAnonymization;
use DbAnonymizer\Plan\TableName;
use DbAnonymizer\Pool\PoolAlias;
use DbAnonymizer\Pool\Seed;
use DbAnonymizer\Sql\SqlExpression;
use DbAnonymizer\Tests\Double\FakeMask;
use DbAnonymizer\Tests\Double\FixedSchemaInspector;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PlanValidationTest extends TestCase
{
    #[Test]
    public function it_should_report_no_problem_for_a_valid_plan(): void
    {
        $plan = new AnonymizationPlan(
            PoolAlias::of('_anon_pool'),
            Seed::of('demo'),
            1000,
            [$this->tableAgainst(TableName::of('users'), ColumnName::of('email'))],
        );

        $problems = PlanValidation::problemsOf($plan, FixedSchemaInspector::of(['users'], ['users' => ['id', 'email']]));

        self::assertTrue($problems->isEmpty());
    }

    #[Test]
    public function it_should_report_an_unknown_table_and_an_unknown_column_together(): void
    {
        $plan = new AnonymizationPlan(
            PoolAlias::of('_anon_pool'),
            Seed::of('demo'),
            1000,
            [
                $this->tableAgainst(TableName::of('unknown_table'), ColumnName::of('email')),
                $this->tableAgainst(TableName::of('users'), ColumnName::of('unknown_column')),
            ],
        );

        $problems = PlanValidation::problemsOf($plan, FixedSchemaInspector::of(['users'], ['users' => ['id']]));

        self::assertCount(2, $problems);
    }

    private function tableAgainst(TableName $table, ColumnName $column): TableAnonymization
    {
        return new TableAnonymization(
            $table,
            ColumnName::of('id'),
            ColumnName::of('id'),
            RowFilter::all(),
            [new ColumnRule($column, FakeMask::returning(SqlExpression::literal('x')))],
        );
    }
}
