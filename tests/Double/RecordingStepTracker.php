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

use DumpLoader\LoadJob\LogTag;
use DumpLoader\LoadJob\Percentage;
use DumpLoader\Step\StepTracker;

final class RecordingStepTracker implements StepTracker
{
    /**
     * @var list<array{tag: LogTag, message: string}>
     */
    private array $logs = [];

    /**
     * @var list<Percentage>
     */
    private array $progressUpdates = [];

    private bool $abortRequested = false;

    public function log(LogTag $tag, string $message): void
    {
        $this->logs[] = ['tag' => $tag, 'message' => $message];
    }

    public function progress(Percentage $percentage): void
    {
        $this->progressUpdates[] = $percentage;
    }

    public function abortRequested(): bool
    {
        return $this->abortRequested;
    }

    public function requestAbort(): void
    {
        $this->abortRequested = true;
    }

    /**
     * @return list<array{tag: LogTag, message: string}>
     */
    public function logs(): array
    {
        return $this->logs;
    }

    /**
     * @return list<Percentage>
     */
    public function progressUpdates(): array
    {
        return $this->progressUpdates;
    }
}
