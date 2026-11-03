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

use DbAnonymizer\Mask\MaskingContext;
use DbAnonymizer\Mask\PoolDependent;
use DbAnonymizer\Pool\PoolColumn;
use DbAnonymizer\Sql\SqlExpression;

final readonly class FakePoolMask implements PoolDependent
{
    /**
     * @param list<PoolColumn> $poolColumns
     */
    private function __construct(
        private SqlExpression $expression,
        private array $poolColumns,
    ) {
    }

    /**
     * @param list<PoolColumn> $poolColumns
     */
    public static function returning(SqlExpression $expression, array $poolColumns): self
    {
        return new self($expression, $poolColumns);
    }

    public function expressionFor(MaskingContext $context): SqlExpression
    {
        return $this->expression;
    }

    public function poolColumns(): iterable
    {
        return $this->poolColumns;
    }
}
