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

use DbAnonymizer\Sql\SqlExpression;

final readonly class RowFilter
{
    private function __construct(
        private ?string $expression,
    ) {
    }

    public static function all(): self
    {
        return new self(null);
    }

    public static function of(string $expression): self
    {
        if ('' === trim($expression)) {
            throw InvalidPlan::because('A row filter expression cannot be empty.');
        }

        return new self($expression);
    }

    public function isAll(): bool
    {
        return null === $this->expression;
    }

    public function toSqlExpression(): ?SqlExpression
    {
        return null === $this->expression ? null : SqlExpression::of($this->expression);
    }
}
