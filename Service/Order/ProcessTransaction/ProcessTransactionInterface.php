<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\ProcessTransaction;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\Resources\Payment;

interface ProcessTransactionInterface
{
    /**
     * This function gets called on a response from Mollie. Only $mollieOrder or $molliePayment will be set, not both.
     */
    public function process(OrderInterface $order, Payment $molliePayment): void;
}
