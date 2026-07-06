<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Order;

/**
 * Module V2 stored an Orders API id (ord_...) in mollie_transaction_id, while V3 only talks to the Payments API.
 * This recognises such a legacy id so the V3 code can fall back to the Orders API for it.
 *
 * @deprecated This is a transitional fallback for V2 orders only. Remove once no ord_ orders remain in flight.
 */
class LegacyOrderTransactionId
{
    private const PREFIX = 'ord_';

    public function matches(string $transactionId): bool
    {
        return str_starts_with($transactionId, self::PREFIX);
    }
}
