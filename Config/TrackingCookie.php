<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Config;

class TrackingCookie
{
    public function __construct(
        public readonly string $cookieName,
        public readonly string $alias,
    ) {}
}
