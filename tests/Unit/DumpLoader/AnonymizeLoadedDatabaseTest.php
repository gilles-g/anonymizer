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

namespace DbAnonymizer\Tests\Unit\DumpLoader;

use DbAnonymizer\DumpLoader\AnonymizeLoadedDatabase;
use DbAnonymizer\Plan\AnonymizationPlan;
use DbAnonymizer\Plan\ColumnName;
use DbAnonymizer\Plan\ColumnRule;
use DbAnonymizer\Plan\RowFilter;
use DbAnonymizer\Plan\TableAnonymization;
use DbAnonymizer\Plan\TableName;
use DbAnonymizer\Pool\PoolAlias;
use DbAnonymizer\Pool\Seed;
use DbAnonymizer\Sql\AnonymizationStatement;
use DbAnonymizer\Sql\SqlExpression;
use DbAnonymizer\Sql\StatementExecutor;
use DbAnonymizer\Tests\Double\FakeMask;
use DbAnonymizer\Tests\Double\InMemoryStatementExecutor;
use DbAnonymizer\Tests\Double\RecordingStepTracker;
use DumpLoader\Dump\Dump;
use DumpLoader\Dump\FileSize;
use DumpLoader\LoadJob\LoadJobId;
use DumpLoader\LoadJob\LoadOptions;
use DumpLoader\LoadJob\Percentage;
use DumpLoader\Step\LoadContext;
use DumpLoader\Step\OptionalStep;
use DumpLoader\Step\StepDefinition;
use DumpLoader\Step\StepFailed;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AnonymizeLoadedDatabaseTest extends TestCase
{
    #[Test]
    public function it_should_definition_is_optional_and_ranked_after_migrations(): void
    {
        $step = new AnonymizeLoadedDatabase(new InMemoryStatementExecutor(), $this->planWithTwoTables());

        self::assertInstanceOf(OptionalStep::class, $step);
        self::assertTrue($step->enabledByDefault());

        $definition = $step->definition();

        self::assertSame('anonymize_database', $definition->name);
        self::assertSame(250, $definition->rank);
        self::assertGreaterThan(StepDefinition::RANK_RUN_MIGRATIONS, $definition->rank);
    }

    #[Test]
    public function it_should_run_reports_progress_after_each_statement(): void
    {
        $step = new AnonymizeLoadedDatabase(new InMemoryStatementExecutor(), $this->planWithTwoTables());
        $tracker = new RecordingStepTracker();

        $step->run($this->loadContext(), $tracker);

        $progress = array_map(static fn (Percentage $p): int => $p->toInt(), $tracker->progressUpdates());

        self::assertSame([50, 100], $progress);
    }

    #[Test]
    public function it_should_run_stops_between_two_statements_when_abort_is_requested(): void
    {
        $executor = new InMemoryStatementExecutor();
        $step = new AnonymizeLoadedDatabase($executor, $this->planWithTwoTables());
        $tracker = new RecordingStepTracker();
        $tracker->requestAbort();

        $step->run($this->loadContext(), $tracker);

        self::assertCount(0, $executor->executed());
    }

    #[Test]
    public function it_should_translate_a_technical_failure_into_a_step_failed(): void
    {
        $executor = new class implements StatementExecutor {
            public function execute(AnonymizationStatement $statement): int
            {
                throw new \RuntimeException('connection refused');
            }
        };

        $step = new AnonymizeLoadedDatabase($executor, $this->planWithTwoTables());

        $this->expectException(StepFailed::class);

        $step->run($this->loadContext(), new RecordingStepTracker());
    }

    private function planWithTwoTables(): AnonymizationPlan
    {
        return new AnonymizationPlan(
            PoolAlias::of('_anon_pool'),
            Seed::of('demo'),
            1000,
            [
                $this->table(TableName::of('users')),
                $this->table(TableName::of('addresses')),
            ],
        );
    }

    private function table(TableName $name): TableAnonymization
    {
        return new TableAnonymization(
            $name,
            ColumnName::of('id'),
            ColumnName::of('id'),
            RowFilter::all(),
            [new ColumnRule(ColumnName::of('email'), FakeMask::returning(SqlExpression::literal('anon@example.test')))],
        );
    }

    private function loadContext(): LoadContext
    {
        return new LoadContext(
            LoadJobId::generate(),
            Dump::fromPath('/tmp/dump.sql', FileSize::fromBytes(10), new \DateTimeImmutable()),
            LoadOptions::none(),
        );
    }
}
