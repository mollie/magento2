<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Order;

use Magento\Sales\Api\Data\OrderInterface;

class UsedMollieApi
{
    const TYPE_ORDERS = 'orders';
    const TYPE_PAYMENTS = 'payments';

    public function execute(OrderInterface $order): string
    {
        $transactionId = $order->getMollieTransactionId() ?? '';
        return substr($transactionId, 0, 4) == 'ord_' ? self::TYPE_ORDERS : self::TYPE_PAYMENTS;
    }
}
