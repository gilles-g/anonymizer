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

use DbAnonymizer\Pool\PoolColumn;

interface PoolDependent extends Mask
{
    /**
     * @return iterable<PoolColumn>
     */
    public function poolColumns(): iterable;
}
