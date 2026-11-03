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

use DbAnonymizer\Plan\ColumnName;
use DbAnonymizer\Plan\TableName;
use DbAnonymizer\Sql\Dialect;
use DbAnonymizer\Sql\UpdateJoinStyle;

final readonly class FakeDialect implements Dialect
{
    private function __construct(
        private UpdateJoinStyle $joinStyle,
    ) {
    }

    public static function mysql(): self
    {
        return new self(UpdateJoinStyle::JoinOnUpdate);
    }

    public static function postgres(): self
    {
        return new self(UpdateJoinStyle::FromClause);
    }

    public function quoteIdentifier(TableName|ColumnName $identifier): string
    {
        return '`'.$identifier->value.'`';
    }

    public function quoteLiteral(string|int|float|bool|null $value): string
    {
        if (null === $value) {
            return 'NULL';
        }

        if (\is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (\is_int($value) || \is_float($value)) {
            return (string) $value;
        }

        return "'".str_replace("'", "''", $value)."'";
    }

    public function updateJoinStyle(): UpdateJoinStyle
    {
        return $this->joinStyle;
    }
}
