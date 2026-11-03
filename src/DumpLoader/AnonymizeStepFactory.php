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

namespace DbAnonymizer\DumpLoader;

use DbAnonymizer\Plan\AnonymizationPlanFactory;
use DbAnonymizer\Sql\StatementExecutor;
use DumpLoader\Step\Step;
use DumpLoader\Step\StepFactory;

final readonly class AnonymizeStepFactory implements StepFactory
{
    public function __construct(
        private StatementExecutor $statementExecutor,
        private AnonymizationPlanFactory $anonymizationPlanFactory,
    ) {
    }

    public static function configurationKey(): string
    {
        return 'anonymization';
    }

    public function create(array $options): Step
    {
        return new AnonymizeLoadedDatabase(
            $this->statementExecutor,
            $this->anonymizationPlanFactory->createFromOptions($options),
        );
    }
}
