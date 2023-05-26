<?php

namespace Mollie\Payment\Model\Client\Orders\Processors;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\Resources\Order;
use Mollie\Payment\Model\Client\OrderProcessorInterface;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;

class PaymentLinkPaymentMethod implements OrderProcessorInterface
{
    public function process(
        OrderInterface $magentoOrder,
        Order $mollieOrder,
        string $type,
        ProcessTransactionResponse $response
    ): ?ProcessTransactionResponse {
        if ($magentoOrder->getPayment()->getMethod() !== 'mollie_methods_paymentlink') {
            return $response;
        }

        $magentoOrder->getPayment()->setAdditionalInformation('payment_link_method_used', $mollieOrder->method);

        return $response;
    }
}
