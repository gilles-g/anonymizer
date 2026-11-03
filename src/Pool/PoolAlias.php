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

use DbAnonymizer\Sql\InvalidIdentifier;

final readonly class PoolAlias
{
    private function __construct(
        public string $value,
    ) {
    }

    public static function of(string $value): self
    {
        if ('' === $value) {
            throw InvalidIdentifier::because('A pool alias cannot be empty.');
        }

        if (1 === preg_match('/[`\'";]/', $value)) {
            throw InvalidIdentifier::because(\sprintf('Pool alias "%s" contains a forbidden character (backtick, quote or semicolon).', $value));
        }

        return new self($value);
    }

    public static function default(): self
    {
        return new self('_anon_pool');
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
