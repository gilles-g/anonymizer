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

namespace DbAnonymizer\Tests\Unit\Run;

use DbAnonymizer\Plan\AnonymizationPlan;
use DbAnonymizer\Plan\ColumnName;
use DbAnonymizer\Plan\ColumnRule;
use DbAnonymizer\Plan\RowFilter;
use DbAnonymizer\Plan\TableAnonymization;
use DbAnonymizer\Plan\TableName;
use DbAnonymizer\Pool\PoolAlias;
use DbAnonymizer\Pool\Seed;
use DbAnonymizer\Run\Anonymizer;
use DbAnonymizer\Sql\SqlExpression;
use DbAnonymizer\Tests\Double\FakeMask;
use DbAnonymizer\Tests\Double\InMemoryStatementExecutor;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AnonymizerTest extends TestCase
{
    #[Test]
    public function it_should_run_executes_every_statement_of_the_plan_and_reports_the_result(): void
    {
        $plan = new AnonymizationPlan(
            PoolAlias::of('_anon_pool'),
            Seed::of('demo'),
            1000,
            [new TableAnonymization(
                TableName::of('users'),
                ColumnName::of('id'),
                ColumnName::of('id'),
                RowFilter::all(),
                [new ColumnRule(ColumnName::of('email'), FakeMask::returning(SqlExpression::literal('anon@example.test')))],
            )],
        );

        $executor = new InMemoryStatementExecutor(affectedRowsPerStatement: 42);
        $report = (new Anonymizer($executor))->run($plan);

        self::assertSame(1, $report->statementsExecuted);
        self::assertSame(42, $report->affectedRows);
        self::assertCount(1, $executor->executed());
    }
}
