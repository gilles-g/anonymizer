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

namespace DbAnonymizer\Mask;

final readonly class MaskOptions
{
    /**
     * @param array<string, mixed> $values
     */
    private function __construct(
        private array $values,
    ) {
    }

    /**
     * @param array<string, mixed> $values
     */
    public static function of(array $values): self
    {
        return new self($values);
    }

    public function has(string $name): bool
    {
        return \array_key_exists($name, $this->values);
    }

    public function string(string $name): string
    {
        $value = $this->required($name);

        if (!\is_string($value)) {
            throw InvalidMaskOptions::because(\sprintf('Option "%s" must be a string, %s given.', $name, get_debug_type($value)));
        }

        return $value;
    }

    public function int(string $name): int
    {
        $value = $this->required($name);

        if (!\is_int($value)) {
            throw InvalidMaskOptions::because(\sprintf('Option "%s" must be an integer, %s given.', $name, get_debug_type($value)));
        }

        return $value;
    }

    private function required(string $name): mixed
    {
        if (!$this->has($name)) {
            throw InvalidMaskOptions::because(\sprintf('Missing required option "%s".', $name));
        }

        return $this->values[$name];
    }
}
