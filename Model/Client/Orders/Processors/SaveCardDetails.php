<?php

namespace Mollie\Payment\Model\Client\Orders\Processors;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\Resources\Order;
use Mollie\Payment\Model\Client\OrderProcessorInterface;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;

class SaveCardDetails implements OrderProcessorInterface
{
    public function process(
        OrderInterface $magentoOrder,
        Order $mollieOrder,
        string $type,
        ProcessTransactionResponse $response
    ): ?ProcessTransactionResponse {
        if (!$mollieOrder->payments() ||
            !$mollieOrder->payments()->offsetGet(0)
        ) {
            return $response;
        }

        $details = $mollieOrder->payments()->offsetGet(0)->details;
        if (!$details) {
            return $response;
        }

        $magentoOrder->getPayment()->setAdditionalInformation('details', json_encode($details));

        return $response;
    }
}
