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

final readonly class AnonymizationReport
{
    private function __construct(
        public int $statementsExecuted,
        public int $affectedRows,
    ) {
    }

    public static function of(int $statementsExecuted, int $affectedRows): self
    {
        return new self($statementsExecuted, $affectedRows);
    }
}
