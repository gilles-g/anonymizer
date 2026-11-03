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
use DbAnonymizer\Sql\Dialect;

final readonly class AnonymizationScript
{
    public function __construct(
        private Dialect $dialect,
    ) {
    }

    public function render(AnonymizationPlan $plan): string
    {
        $lines = [];

        foreach ($plan->statements() as $statement) {
            $lines[] = $statement->renderInlined($this->dialect).';';
        }

        return implode("\n", $lines)."\n";
    }
}
