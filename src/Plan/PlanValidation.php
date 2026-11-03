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

namespace DbAnonymizer\Plan;

use DbAnonymizer\Sql\SchemaInspector;

final readonly class PlanValidation
{
    public static function problemsOf(AnonymizationPlan $plan, SchemaInspector $inspector): ProblemList
    {
        $problems = [];

        foreach ($plan->tables() as $table) {
            if (!$inspector->hasTable($table->table())) {
                $problems[] = \sprintf('Table "%s" does not exist.', $table->table()->value);

                continue;
            }

            foreach ($table->columns() as $column) {
                if (!$inspector->hasColumn($table->table(), $column)) {
                    $problems[] = \sprintf('Column "%s.%s" does not exist.', $table->table()->value, $column->value);
                }
            }
        }

        return ProblemList::of($problems);
    }
}
