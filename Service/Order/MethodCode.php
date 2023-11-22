<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order;

use Magento\Sales\Api\Data\OrderInterface;

class MethodCode
{
    public function execute(OrderInterface $order): string
    {
        $method = $order->getPayment()->getMethodInstance()->getCode();

        if ($method == 'mollie_methods_paymentlink') {
            return $this->paymentLinkMethod($order);
        }

        if ($method == 'mollie_methods_paymentlink' || strstr($method, 'mollie_methods') === false) {
            return '';
        }

        return str_replace('mollie_methods_', '', $method);
    }

    private function paymentLinkMethod(OrderInterface $order): string
    {
        $additionalInformation = $order->getPayment()->getAdditionalInformation();
        if (!$additionalInformation || !array_key_exists('limited_methods', $additionalInformation)) {
            return '';
        }

        if (count($additionalInformation['limited_methods']) !== 1) {
            return '';
        }

        return str_replace('mollie_methods_', '', $additionalInformation['limited_methods'][0]);
    }
}
