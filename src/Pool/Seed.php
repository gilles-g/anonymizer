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

final readonly class Seed
{
    private function __construct(
        public string $value,
    ) {
    }

    public static function of(string $value): self
    {
        if ('' === $value) {
            throw new \InvalidArgumentException('A seed cannot be empty.');
        }

        return new self($value);
    }

    public function asInteger(): int
    {
        return crc32($this->value);
    }
}
