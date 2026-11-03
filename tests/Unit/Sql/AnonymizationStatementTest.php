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

namespace DbAnonymizer\Tests\Unit\Sql;

use DbAnonymizer\Plan\ColumnName;
use DbAnonymizer\Plan\TableName;
use DbAnonymizer\Pool\PoolAlias;
use DbAnonymizer\Sql\AnonymizationStatement;
use DbAnonymizer\Sql\PoolJoin;
use DbAnonymizer\Sql\SqlExpression;
use DbAnonymizer\Tests\Double\FakeDialect;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AnonymizationStatementTest extends TestCase
{
    #[Test]
    public function it_should_update_without_join_renders_a_plain_statement(): void
    {
        $statement = AnonymizationStatement::update(
            TableName::of('users'),
            ['email' => SqlExpression::literal('anon@example.test')],
        );

        $rendered = $statement->render(FakeDialect::mysql());

        self::assertSame('UPDATE `users` SET `email` = :email_literal', $rendered->sql);
        self::assertSame(['email_literal' => 'anon@example.test'], $rendered->bindings);
        self::assertFalse($statement->hasPoolJoin());
    }

    #[Test]
    public function it_should_render_inlines_nothing_while_render_inlined_inlines_bound_values(): void
    {
        $statement = AnonymizationStatement::update(
            TableName::of('users'),
            ['email' => SqlExpression::literal('anon@example.test')],
        );

        self::assertSame(
            "UPDATE `users` SET `email` = 'anon@example.test'",
            $statement->renderInlined(FakeDialect::mysql()),
        );
    }

    #[Test]
    public function it_should_update_with_pool_join_is_present_when_a_join_is_given(): void
    {
        $statement = AnonymizationStatement::update(
            TableName::of('users'),
            ['first_name' => SqlExpression::literal('Camille')],
            PoolJoin::of(PoolAlias::of('_anon_pool'), ColumnName::of('id'), 1000),
        );

        self::assertTrue($statement->hasPoolJoin());
    }

    #[Test]
    public function it_should_pool_join_renders_as_a_join_on_mysql(): void
    {
        $statement = AnonymizationStatement::update(
            TableName::of('users'),
            ['first_name' => SqlExpression::literal('Camille')],
            PoolJoin::of(PoolAlias::of('_anon_pool'), ColumnName::of('id'), 1000),
        );

        self::assertSame(
            "UPDATE `users` JOIN `_anon_pool` ON `_anon_pool`.`n` = `users`.`id` % 1000 SET `first_name` = 'Camille'",
            $statement->renderInlined(FakeDialect::mysql()),
        );
    }

    #[Test]
    public function it_should_pool_join_renders_as_a_from_clause_on_postgres(): void
    {
        $statement = AnonymizationStatement::update(
            TableName::of('users'),
            ['first_name' => SqlExpression::literal('Camille')],
            PoolJoin::of(PoolAlias::of('_anon_pool'), ColumnName::of('id'), 1000),
        );

        self::assertSame(
            "UPDATE `users` SET `first_name` = 'Camille' FROM `_anon_pool` WHERE `_anon_pool`.`n` = `users`.`id` % 1000",
            $statement->renderInlined(FakeDialect::postgres()),
        );
    }

    #[Test]
    public function it_should_row_filter_is_appended_as_where_on_a_statement_without_join(): void
    {
        $statement = AnonymizationStatement::update(
            TableName::of('users'),
            ['email' => SqlExpression::literal('anon@example.test')],
            null,
            SqlExpression::of("role <> 'ROLE_DEMO'"),
        );

        self::assertSame(
            "UPDATE `users` SET `email` = 'anon@example.test' WHERE role <> 'ROLE_DEMO'",
            $statement->renderInlined(FakeDialect::mysql()),
        );
    }

    #[Test]
    public function it_should_raw_statement_is_rendered_verbatim(): void
    {
        $statement = AnonymizationStatement::raw(SqlExpression::of(
            'DROP TABLE IF EXISTS {{pool}}',
            ['pool' => TableName::of('_anon_pool')],
        ));

        self::assertSame('DROP TABLE IF EXISTS `_anon_pool`', $statement->renderInlined(FakeDialect::mysql()));
        self::assertFalse($statement->hasPoolJoin());
    }

    #[Test]
    public function it_should_update_requires_at_least_one_assignment(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        AnonymizationStatement::update(TableName::of('users'), []);
    }
}
