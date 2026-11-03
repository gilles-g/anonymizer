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

use DbAnonymizer\Mask\InvalidMaskOptions;
use DbAnonymizer\Mask\UnknownMask;
use DbAnonymizer\Plan\AnonymizationPlanFactory;
use DbAnonymizer\Plan\InvalidPlan;
use DbAnonymizer\Plan\TableAnonymization;
use DbAnonymizer\Tests\Double\FakeMaskFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AnonymizationPlanFactoryTest extends TestCase
{
    #[Test]
    public function it_should_create_a_plan_with_one_table_per_entry(): void
    {
        $plan = $this->factory()->createFromOptions([
            'seed' => 'abc',
            'tables' => [
                'users' => ['columns' => ['email' => ['mask' => 'fixed', 'value' => 'x@example.com']]],
                'orders' => [
                    'identity' => 'order_id',
                    'pool_key' => 'customer_id',
                    'where' => 'status = "done"',
                    'columns' => ['note' => ['mask' => 'fixed', 'value' => '']],
                ],
            ],
        ]);

        self::assertSame(['users', 'orders'], array_map(static fn (TableAnonymization $table): string => $table->table()->value, $plan->tables()));
        self::assertCount(2, [...$plan->statements()]);
    }

    #[Test]
    public function it_should_honour_the_pool_alias_and_size(): void
    {
        $plan = $this->factory()->createFromOptions([
            'seed' => 'abc',
            'pool' => 'my_pool',
            'pool_size' => 10,
            'tables' => ['users' => ['columns' => ['email' => ['mask' => 'fixed', 'value' => 'x']]]],
        ]);

        self::assertCount(1, [...$plan->statements()]);
    }

    #[Test]
    public function it_should_refuse_a_plan_without_table(): void
    {
        $this->expectException(InvalidPlan::class);
        $this->expectExceptionMessage('There is no table to anonymize under "tables".');

        $this->factory()->createFromOptions(['seed' => 'abc', 'tables' => []]);
    }

    #[Test]
    public function it_should_refuse_a_plan_without_seed(): void
    {
        $this->expectException(InvalidMaskOptions::class);
        $this->expectExceptionMessage('Missing required option "seed".');

        $this->factory()->createFromOptions(['tables' => ['users' => ['columns' => ['email' => ['mask' => 'fixed', 'value' => 'x']]]]]);
    }

    /**
     * @return iterable<string, array{array<string, mixed>, string}>
     */
    public static function invalidTables(): iterable
    {
        yield 'table not a map' => [['users' => 'email'], 'Table "users" must be a map of options.'];
        yield 'table without columns' => [['users' => ['identity' => 'id']], 'Table "users" needs a "columns" map.'];
        yield 'column not a map' => [['users' => ['columns' => ['email' => 'fixed']]], 'Each column must be a map of mask options.'];
    }

    /**
     * @param array<string, mixed> $tables
     */
    #[Test]
    #[DataProvider('invalidTables')]
    public function it_should_refuse_a_malformed_table(array $tables, string $message): void
    {
        $this->expectException(InvalidPlan::class);
        $this->expectExceptionMessage($message);

        $this->factory()->createFromOptions(['seed' => 'abc', 'tables' => $tables]);
    }

    #[Test]
    public function it_should_refuse_a_mask_no_factory_supports(): void
    {
        $this->expectException(UnknownMask::class);
        $this->expectExceptionMessage('Unknown mask "shuffle".');

        $this->factory()->createFromOptions(['seed' => 'abc', 'tables' => ['users' => ['columns' => ['email' => ['mask' => 'shuffle']]]]]);
    }

    private function factory(): AnonymizationPlanFactory
    {
        return new AnonymizationPlanFactory([new FakeMaskFactory()]);
    }
}
