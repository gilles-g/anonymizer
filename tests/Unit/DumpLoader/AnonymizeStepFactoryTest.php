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
use DbAnonymizer\DumpLoader\AnonymizeStepFactory;
use DbAnonymizer\Plan\AnonymizationPlanFactory;
use DbAnonymizer\Tests\Double\FakeMaskFactory;
use DbAnonymizer\Tests\Double\InMemoryStatementExecutor;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class AnonymizeStepFactoryTest extends TestCase
{
    #[Test]
    public function it_should_answer_to_the_anonymization_key(): void
    {
        self::assertSame('anonymization', AnonymizeStepFactory::configurationKey());
    }

    #[Test]
    public function it_should_build_the_step_from_the_configured_plan(): void
    {
        $factory = new AnonymizeStepFactory(new InMemoryStatementExecutor(), new AnonymizationPlanFactory([new FakeMaskFactory()]));

        $step = $factory->create([
            'seed' => 'abc',
            'tables' => ['users' => ['columns' => ['email' => ['mask' => 'fixed', 'value' => 'x@example.com']]]],
        ]);

        self::assertInstanceOf(AnonymizeLoadedDatabase::class, $step);
    }
}
