<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Customer;

use Mollie\Api\Exceptions\ApiException;

class MollieCustomerNoLongerExists
{
    private const GONE_STATUS_CODE = 410;
    private const GONE_RESOURCE = 'customer';
    private const GONE_DESCRIPTION = 'no longer available';

    public function check(ApiException $exception): bool
    {
        if ($exception->getStatusCode() !== self::GONE_STATUS_CODE) {
            return false;
        }

        $message = strtolower($exception->getPlainMessage());

        return str_contains($message, self::GONE_RESOURCE) && str_contains($message, self::GONE_DESCRIPTION);
    }
}
