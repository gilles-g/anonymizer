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

use DbAnonymizer\Sql\AnonymizationStatement;
use DbAnonymizer\Sql\StatementExecutor;
use Doctrine\DBAL\Connection;

final readonly class DbalStatementExecutor implements StatementExecutor
{
    public function __construct(
        private Connection $connection,
        private DbalDialect $dialect,
    ) {
    }

    public function execute(AnonymizationStatement $statement): int
    {
        $rendered = $statement->render($this->dialect);

        return (int) $this->connection->executeStatement($rendered->sql, $rendered->bindings);
    }
}
