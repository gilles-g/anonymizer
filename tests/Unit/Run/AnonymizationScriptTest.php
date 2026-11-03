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
use DbAnonymizer\Run\AnonymizationScript;
use DbAnonymizer\Sql\SqlExpression;
use DbAnonymizer\Tests\Double\FakeDialect;
use DbAnonymizer\Tests\Double\FakeMask;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AnonymizationScriptTest extends TestCase
{
    #[Test]
    public function it_should_render_produces_one_terminated_statement_per_line(): void
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

        $script = (new AnonymizationScript(FakeDialect::mysql()))->render($plan);

        self::assertSame("UPDATE `users` SET `email` = 'anon@example.test';\n", $script);
    }
}
