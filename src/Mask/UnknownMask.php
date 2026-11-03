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

final class UnknownMask extends \RuntimeException
{
    /**
     * @param list<string> $known
     */
    public static function named(string $name, array $known): self
    {
        $suggestion = self::closestMatch($name, $known);

        return new self(null === $suggestion
            ? \sprintf('Unknown mask "%s".', $name)
            : \sprintf('Unknown mask "%s". Did you mean "%s"?', $name, $suggestion));
    }

    /**
     * @param list<string> $known
     */
    private static function closestMatch(string $name, array $known): ?string
    {
        $closest = null;
        $shortestDistance = null;

        foreach ($known as $candidate) {
            $distance = levenshtein($name, $candidate);

            if (null === $shortestDistance || $distance < $shortestDistance) {
                $shortestDistance = $distance;
                $closest = $candidate;
            }
        }

        return null !== $closest && $shortestDistance <= 3 ? $closest : null;
    }
}
