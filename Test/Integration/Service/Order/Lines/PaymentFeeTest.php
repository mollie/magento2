<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order\Lines;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class PaymentFeeTest extends IntegrationTestCase
{
    public function testOrderHasPaymentFee()
    {
        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);

        $order->setData('mollie_payment_fee', 1);
        $order->setData('base_mollie_payment_fee', 1);

        /** @var PaymentFee $instance */
        $instance = $this->objectManager->create(PaymentFee::class);

        $this->assertTrue($instance->orderHasPaymentFee($order));
    }
}
