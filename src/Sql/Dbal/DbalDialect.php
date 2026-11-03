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

namespace DbAnonymizer\Sql\Dbal;

use DbAnonymizer\Plan\ColumnName;
use DbAnonymizer\Plan\TableName;
use DbAnonymizer\Sql\Dialect;
use DbAnonymizer\Sql\UpdateJoinStyle;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;

final readonly class DbalDialect implements Dialect
{
    public function __construct(
        private Connection $connection,
    ) {
    }

    public function quoteIdentifier(TableName|ColumnName $identifier): string
    {
        return $this->connection->quoteIdentifier($identifier->value);
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

        return $this->connection->quote($value);
    }

    public function updateJoinStyle(): UpdateJoinStyle
    {
        return $this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform
            ? UpdateJoinStyle::FromClause
            : UpdateJoinStyle::JoinOnUpdate;
    }
}
