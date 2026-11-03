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

namespace DbAnonymizer\Tests\Double;

use DbAnonymizer\Mask\Mask;
use DbAnonymizer\Mask\MaskingContext;
use DbAnonymizer\Sql\SqlExpression;

final readonly class FakeMask implements Mask
{
    private function __construct(
        private SqlExpression $expression,
    ) {
    }

    public static function returning(SqlExpression $expression): self
    {
        return new self($expression);
    }

    public function expressionFor(MaskingContext $context): SqlExpression
    {
        return $this->expression;
    }
}
