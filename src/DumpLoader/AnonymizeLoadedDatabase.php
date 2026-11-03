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

namespace DbAnonymizer\DumpLoader;

use DbAnonymizer\Plan\AnonymizationPlan;
use DbAnonymizer\Sql\StatementExecutor;
use DumpLoader\LoadJob\Percentage;
use DumpLoader\Step\LoadContext;
use DumpLoader\Step\OptionalStep;
use DumpLoader\Step\StepDefinition;
use DumpLoader\Step\StepFailed;
use DumpLoader\Step\StepTracker;

final readonly class AnonymizeLoadedDatabase implements OptionalStep
{
    public function __construct(
        private StatementExecutor $executor,
        private AnonymizationPlan $plan,
    ) {
    }

    public function definition(): StepDefinition
    {
        return new StepDefinition(
            name: 'anonymize_database',
            label: 'Anonymize the database',
            description: 'Runs the configured anonymization rules against the loaded database.',
            rank: 250,
            weight: 3,
        );
    }

    public function enabledByDefault(): bool
    {
        return true;
    }

    public function run(LoadContext $context, StepTracker $tracker): void
    {
        $statements = [...$this->plan->statements()];
        $total = \count($statements);

        foreach ($statements as $index => $statement) {
            if ($tracker->abortRequested()) {
                return;
            }

            try {
                $this->executor->execute($statement);
            } catch (\Throwable $exception) {
                throw StepFailed::because('The database could not be anonymized.', $exception);
            }

            $tracker->progress(Percentage::of(($index + 1) / $total * 100));
        }
    }
}
