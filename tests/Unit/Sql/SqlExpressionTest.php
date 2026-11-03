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
use DbAnonymizer\Pool\PoolAlias;
use DbAnonymizer\Pool\PoolColumn;
use DbAnonymizer\Sql\SqlExpression;
use DbAnonymizer\Tests\Double\FakeDialect;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class SqlExpressionTest extends TestCase
{
    #[Test]
    public function it_should_literal_binds_its_value_and_leaves_it_out_of_the_sql_text(): void
    {
        $expression = SqlExpression::literal('secret');
        $rendered = $expression->render(FakeDialect::mysql());

        self::assertSame(':literal', $rendered->sql);
        self::assertSame(['literal' => 'secret'], $rendered->bindings);
    }

    #[Test]
    public function it_should_column_delegates_quoting_to_the_dialect(): void
    {
        $expression = SqlExpression::column(ColumnName::of('email'));

        self::assertSame('`email`', $expression->render(FakeDialect::mysql())->sql);
    }

    #[Test]
    public function it_should_pool_column_qualifies_the_column_with_the_pool_alias(): void
    {
        $expression = SqlExpression::poolColumn(PoolAlias::of('_anon_pool'), PoolColumn::of('firstName', ColumnName::of('first')));

        self::assertSame('`_anon_pool`.`first`', $expression->render(FakeDialect::mysql())->sql);
    }

    #[Test]
    public function it_should_of_separates_structural_text_identifiers_and_bindings(): void
    {
        $expression = SqlExpression::of(
            'LEFT({{column}}, :length)',
            ['column' => ColumnName::of('phone')],
            ['length' => 3],
        );

        $rendered = $expression->render(FakeDialect::mysql());

        self::assertSame('LEFT(`phone`, :length)', $rendered->sql);
        self::assertSame(['length' => 3], $rendered->bindings);
    }

    #[Test]
    public function it_should_render_inlined_substitutes_identifiers_and_quoted_literals(): void
    {
        $expression = SqlExpression::of(
            'LEFT({{column}}, :length)',
            ['column' => ColumnName::of('phone')],
            ['length' => 3],
        );

        self::assertSame('LEFT(`phone`, 3)', $expression->renderInlined(FakeDialect::mysql()));
    }

    #[Test]
    public function it_should_render_inlined_escapes_an_apostrophe_in_a_literal(): void
    {
        $expression = SqlExpression::literal("O'Brien");

        self::assertSame("'O''Brien'", $expression->renderInlined(FakeDialect::mysql()));
    }
}
