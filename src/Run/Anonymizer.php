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

namespace DbAnonymizer\Run;

use DbAnonymizer\Plan\AnonymizationPlan;
use DbAnonymizer\Sql\StatementExecutor;

final readonly class Anonymizer
{
    public function __construct(
        private StatementExecutor $executor,
    ) {
    }

    public function run(AnonymizationPlan $plan): AnonymizationReport
    {
        $statementsExecuted = 0;
        $affectedRows = 0;

        foreach ($plan->statements() as $statement) {
            $affectedRows += $this->executor->execute($statement);
            ++$statementsExecuted;
        }

        return AnonymizationReport::of($statementsExecuted, $affectedRows);
    }
}
