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

use DbAnonymizer\Sql\InvalidIdentifier;

final readonly class ColumnName
{
    private function __construct(
        public string $value,
    ) {
    }

    public static function of(string $value): self
    {
        if ('' === $value) {
            throw InvalidIdentifier::because('A column name cannot be empty.');
        }

        if (1 === preg_match('/[`\'";]/', $value)) {
            throw InvalidIdentifier::because(\sprintf('Column name "%s" contains a forbidden character (backtick, quote or semicolon).', $value));
        }

        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
