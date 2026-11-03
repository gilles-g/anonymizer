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

use DbAnonymizer\Mask\Mask;
use DbAnonymizer\Mask\MaskingContext;

final readonly class ColumnRule
{
    public function __construct(
        private ColumnName $column,
        private Mask $mask,
    ) {
    }

    public function column(): ColumnName
    {
        return $this->column;
    }

    public function mask(): Mask
    {
        return $this->mask;
    }

    public function assignment(MaskingContext $context): Assignment
    {
        return Assignment::of($this->column, $this->mask->expressionFor($context));
    }
}
