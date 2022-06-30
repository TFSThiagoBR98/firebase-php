<?php

declare(strict_types=1);

namespace Kreait\Firebase\Contract;

use InvalidArgumentException;

interface AppCheck
{
    /**
     * @param \Stringable|string $appCheckToken
     *
     * @throws InvalidArgumentException
     */
    public function verifyToken($appCheckToken): bool;
}
