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

final class TableAnonymizationTest extends TestCase
{
    #[Test]
    public function it_should_produce_a_single_update_statement_covering_every_column_rule(): void
    {
        $table = new TableAnonymization(
            TableName::of('users'),
            ColumnName::of('id'),
            ColumnName::of('id'),
            RowFilter::all(),
            [
                new ColumnRule(ColumnName::of('first_name'), FakeMask::returning(SqlExpression::literal('Camille'))),
                new ColumnRule(ColumnName::of('last_name'), FakeMask::returning(SqlExpression::literal('Rousseau'))),
            ],
        );

        $statement = $table->statement(PoolAlias::of('_anon_pool'), Seed::of('demo'), 1000);

        self::assertSame(
            "UPDATE `users` SET `first_name` = 'Camille', `last_name` = 'Rousseau'",
            $statement->renderInlined(FakeDialect::mysql()),
        );
    }

    #[Test]
    public function it_should_pool_join_is_added_only_when_a_rule_is_pool_dependent(): void
    {
        $table = new TableAnonymization(
            TableName::of('users'),
            ColumnName::of('id'),
            ColumnName::of('id'),
            RowFilter::all(),
            [new ColumnRule(ColumnName::of('first_name'), FakeMask::returning(SqlExpression::literal('Camille')))],
        );

        self::assertFalse($table->statement(PoolAlias::of('_anon_pool'), Seed::of('demo'), 1000)->hasPoolJoin());

        $poolDependentTable = new TableAnonymization(
            TableName::of('users'),
            ColumnName::of('id'),
            ColumnName::of('id'),
            RowFilter::all(),
            [new ColumnRule(ColumnName::of('first_name'), FakePoolMask::returning(
                SqlExpression::literal('Camille'),
                [PoolColumn::of('firstName', ColumnName::of('first'))],
            ))],
        );

        self::assertTrue($poolDependentTable->statement(PoolAlias::of('_anon_pool'), Seed::of('demo'), 1000)->hasPoolJoin());
    }

    #[Test]
    public function it_should_row_filter_all_adds_no_where_clause(): void
    {
        $table = new TableAnonymization(
            TableName::of('users'),
            ColumnName::of('id'),
            ColumnName::of('id'),
            RowFilter::all(),
            [new ColumnRule(ColumnName::of('email'), FakeMask::returning(SqlExpression::literal('anon@example.test')))],
        );

        $sql = $table->statement(PoolAlias::of('_anon_pool'), Seed::of('demo'), 1000)->renderInlined(FakeDialect::mysql());

        self::assertStringNotContainsString('WHERE', $sql);
    }
}
