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
    /**
     * @var string
     */
    private $expiresAtMethod = '';

    public function execute(OrderInterface $order): string
    {
        $method = $order->getPayment()->getMethodInstance()->getCode();
        $this->expiresAtMethod = $method;

        if ($method == 'mollie_methods_googlepay')  {
            return 'creditcard';
        }

        if ($method == 'mollie_methods_paymentlink') {
            return $this->paymentLinkMethod($order);
        }

        if (strstr($method, 'mollie_methods') === false) {
            return '';
        }

        return str_replace('mollie_methods_', '', $method);
    }

    /*
     * From which method do we need to get the expires_at date? When a specific method is selected, we use that.
     * When the payment link is used, we use the first limited method. When the payment link has multiple methods,
     * we use the payment link settings to determine the expires_at date.
     */
    public function getExpiresAtMethod(): string
    {
        return str_replace('mollie_methods_', '', $this->expiresAtMethod);
    }

    private function paymentLinkMethod(OrderInterface $order): string
    {
        $additionalInformation = $order->getPayment()->getAdditionalInformation();
        if (!$additionalInformation || !array_key_exists('limited_methods', $additionalInformation)) {
            return '';
        }

        if (!is_array($additionalInformation['limited_methods']) || count($additionalInformation['limited_methods']) !== 1) {
            $this->expiresAtMethod = 'paymentlink';
            return '';
        }

        $this->expiresAtMethod = $additionalInformation['limited_methods'][0];

        return str_replace('mollie_methods_', '', $additionalInformation['limited_methods'][0]);
    }
}
