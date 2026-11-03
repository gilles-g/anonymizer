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

/**
 * @implements \IteratorAggregate<int, string>
 */
final readonly class ProblemList implements \Countable, \IteratorAggregate
{
    /**
     * @param list<string> $problems
     */
    private function __construct(
        private array $problems,
    ) {
    }

    public static function empty(): self
    {
        return new self([]);
    }

    /**
     * @param list<string> $problems
     */
    public static function of(array $problems): self
    {
        return new self($problems);
    }

    public function isEmpty(): bool
    {
        return [] === $this->problems;
    }

    public function count(): int
    {
        return \count($this->problems);
    }

    /**
     * @return \ArrayIterator<int, string>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->problems);
    }
}
