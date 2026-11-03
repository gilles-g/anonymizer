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

namespace DbAnonymizer\Pool;

use DbAnonymizer\Plan\ColumnName;

final readonly class PoolColumn
{
    private function __construct(
        public string $formatter,
        public ColumnName $column,
        public int $offset,
    ) {
    }

    public static function of(string $formatter, ColumnName $column, int $offset = 0): self
    {
        if ('' === $formatter) {
            throw new \InvalidArgumentException('A pool column formatter cannot be empty.');
        }

        return new self($formatter, $column, $offset);
    }

    public function equals(self $other): bool
    {
        return $this->formatter === $other->formatter
            && $this->column->equals($other->column)
            && $this->offset === $other->offset;
    }
}
