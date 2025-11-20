<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Mollie\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\Resources\Payment;

class SaveSendcloudMetadata
{
    public function execute(Payment $payment, OrderInterface $order): void
    {
        if (!property_exists($payment, 'details') ||
            !is_object($payment->details) ||
            !property_exists($payment->details, 'idealExpressMetadata') ||
            !is_object($payment->details->idealExpressMetadata)
        ) {
            return;
        }

        $order->getPayment()->setAdditionalInformation(
            'mollie_ideal_express_metadata',
            json_encode($payment->details->idealExpressMetadata)
        );
    }
}
