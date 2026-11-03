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

use DbAnonymizer\Sql\AnonymizationStatement;
use DbAnonymizer\Sql\StatementExecutor;

final class InMemoryStatementExecutor implements StatementExecutor
{
    /**
     * @var list<AnonymizationStatement>
     */
    private array $executed = [];

    public function __construct(
        private readonly int $affectedRowsPerStatement = 1,
    ) {
    }

    public function execute(AnonymizationStatement $statement): int
    {
        $this->executed[] = $statement;

        return $this->affectedRowsPerStatement;
    }

    /**
     * @return list<AnonymizationStatement>
     */
    public function executed(): array
    {
        return $this->executed;
    }
}
