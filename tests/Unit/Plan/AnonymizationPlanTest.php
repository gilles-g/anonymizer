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

use DbAnonymizer\Mask\Mask;
use DbAnonymizer\Plan\AnonymizationPlan;
use DbAnonymizer\Plan\ColumnName;
use DbAnonymizer\Plan\ColumnRule;
use DbAnonymizer\Plan\RowFilter;
use DbAnonymizer\Plan\TableAnonymization;
use DbAnonymizer\Plan\TableName;
use DbAnonymizer\Pool\PoolAlias;
use DbAnonymizer\Pool\PoolColumn;
use DbAnonymizer\Pool\Seed;
use DbAnonymizer\Sql\SqlExpression;
use DbAnonymizer\Tests\Double\FakeDialect;
use DbAnonymizer\Tests\Double\FakeMask;
use DbAnonymizer\Tests\Double\FakePoolMask;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AnonymizationPlanTest extends TestCase
{
    #[Test]
    public function it_should_statements_without_pool_usage_skip_pool_setup_and_teardown(): void
    {
        $plan = new AnonymizationPlan(
            PoolAlias::of('_anon_pool'),
            Seed::of('demo'),
            1000,
            [$this->tableUsing(FakeMask::returning(SqlExpression::literal('Camille')))],
        );

        $statements = [...$plan->statements()];

        self::assertCount(1, $statements);
    }

    #[Test]
    public function it_should_sequence_pool_setup_then_tables_then_pool_teardown(): void
    {
        $plan = new AnonymizationPlan(
            PoolAlias::of('_anon_pool'),
            Seed::of('demo'),
            1000,
            [$this->tableUsing($this->poolMask('first'))],
        );

        $statements = [...$plan->statements()];

        self::assertCount(3, $statements);
        self::assertStringContainsString('CREATE TEMPORARY TABLE', $statements[0]->renderInlined(FakeDialect::mysql()));
        self::assertStringContainsString('UPDATE', $statements[1]->renderInlined(FakeDialect::mysql()));
        self::assertStringContainsString('DROP TABLE', $statements[2]->renderInlined(FakeDialect::mysql()));
    }

    #[Test]
    public function it_should_identical_pool_columns_across_tables_are_deduplicated(): void
    {
        $plan = new AnonymizationPlan(
            PoolAlias::of('_anon_pool'),
            Seed::of('demo'),
            1000,
            [
                $this->tableUsing($this->poolMask('first'), TableName::of('users')),
                $this->tableUsing($this->poolMask('first'), TableName::of('contacts')),
            ],
        );

        $poolSetup = [...$plan->statements()][0]->renderInlined(FakeDialect::mysql());

        self::assertSame(1, substr_count($poolSetup, '`first`'));
    }

    private function poolMask(string $poolColumn): FakePoolMask
    {
        return FakePoolMask::returning(
            SqlExpression::literal('Camille'),
            [PoolColumn::of('firstName', ColumnName::of($poolColumn))],
        );
    }

    private function tableUsing(Mask $mask, ?TableName $table = null): TableAnonymization
    {
        return new TableAnonymization(
            $table ?? TableName::of('users'),
            ColumnName::of('id'),
            ColumnName::of('id'),
            RowFilter::all(),
            [new ColumnRule(ColumnName::of('first_name'), $mask)],
        );
    }
}
