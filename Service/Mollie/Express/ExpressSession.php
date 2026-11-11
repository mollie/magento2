<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Express;

class ExpressSession
{
    public function __construct(
        public readonly string $sessionId,
        public readonly string $clientAccessToken,
        public readonly string $paymentToken,
    ) {}
}
