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
use DbAnonymizer\Mask\MaskFactory;
use DbAnonymizer\Mask\MaskOptions;
use DbAnonymizer\Mask\UnknownMask;
use DbAnonymizer\Pool\PoolAlias;
use DbAnonymizer\Pool\Seed;

final readonly class AnonymizationPlanFactory
{
    private const DEFAULT_POOL_SIZE = 1000;

    /**
     * @param iterable<MaskFactory> $maskFactories
     */
    public function __construct(
        private iterable $maskFactories,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function createFromOptions(array $options): AnonymizationPlan
    {
        $plan = MaskOptions::of($options);
        $tables = $options['tables'] ?? null;

        if (!\is_array($tables) || [] === $tables) {
            throw InvalidPlan::because('There is no table to anonymize under "tables".');
        }

        $anonymizations = [];

        foreach ($tables as $table => $definition) {
            $anonymizations[] = $this->createTableAnonymization((string) $table, $definition);
        }

        return new AnonymizationPlan(
            $plan->has('pool') ? PoolAlias::of($plan->string('pool')) : PoolAlias::default(),
            Seed::of($plan->string('seed')),
            $plan->has('pool_size') ? $plan->int('pool_size') : self::DEFAULT_POOL_SIZE,
            $anonymizations,
        );
    }

    private function createTableAnonymization(string $table, mixed $definition): TableAnonymization
    {
        if (!\is_array($definition)) {
            throw InvalidPlan::because(\sprintf('Table "%s" must be a map of options.', $table));
        }

        $options = MaskOptions::of($this->withStringKeys($definition));
        $columns = $definition['columns'] ?? null;

        if (!\is_array($columns)) {
            throw InvalidPlan::because(\sprintf('Table "%s" needs a "columns" map.', $table));
        }

        $identity = ColumnName::of($options->has('identity') ? $options->string('identity') : 'id');
        $rules = [];

        foreach ($columns as $column => $mask) {
            $rules[] = new ColumnRule(ColumnName::of((string) $column), $this->createMask($mask));
        }

        return new TableAnonymization(
            TableName::of($table),
            $identity,
            $options->has('pool_key') ? ColumnName::of($options->string('pool_key')) : $identity,
            $options->has('where') ? RowFilter::of($options->string('where')) : RowFilter::all(),
            $rules,
        );
    }

    /**
     * @param array<mixed, mixed> $values
     *
     * @return array<string, mixed>
     */
    private function withStringKeys(array $values): array
    {
        return array_combine(array_map(strval(...), array_keys($values)), array_values($values));
    }

    private function createMask(mixed $definition): Mask
    {
        if (!\is_array($definition)) {
            throw InvalidPlan::because('Each column must be a map of mask options.');
        }

        $options = MaskOptions::of($this->withStringKeys($definition));
        $name = $options->string('mask');

        foreach ($this->maskFactories as $maskFactory) {
            if ($maskFactory->supports($name)) {
                return $maskFactory->create($options);
            }
        }

        throw UnknownMask::named($name, []);
    }
}
