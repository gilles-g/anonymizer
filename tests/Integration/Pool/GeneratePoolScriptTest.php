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

namespace DbAnonymizer\Tests\Integration\Pool;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

final class GeneratePoolScriptTest extends TestCase
{
    #[Test]
    public function it_should_two_runs_with_the_same_seed_produce_identical_output(): void
    {
        $script = \dirname(__DIR__, 3).'/scripts/generate-pool.sh';

        $first = new Process([$script, '-c', 'firstName:first,lastName:last,city:city', '-s', '50', '-S', 'reproducibility-test']);
        $first->mustRun();

        $second = new Process([$script, '-c', 'firstName:first,lastName:last,city:city', '-s', '50', '-S', 'reproducibility-test']);
        $second->mustRun();

        self::assertSame($first->getOutput(), $second->getOutput());
        self::assertNotSame('', $first->getOutput());
    }
}
