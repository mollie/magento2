<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie;

class AsyncPaymentMethods
{
    /**
     * @param string[] $methods
     */
    public function __construct(
        private readonly array $methods = [],
    ) {}

    public function contains(?string $method): bool
    {
        if ($method === null) {
            return false;
        }

        return in_array(str_replace('mollie_methods_', '', $method), $this->methods, true);
    }

    /**
     * @return string[]
     */
    public function all(): array
    {
        return array_values($this->methods);
    }
}
