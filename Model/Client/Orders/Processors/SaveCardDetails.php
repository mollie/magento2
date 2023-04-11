<?php

namespace Mollie\Payment\Model\Client\Orders\Processors;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\Resources\Order;
use Mollie\Payment\Model\Client\OrderProcessorInterface;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Service\Order\SaveAdditionalInformationDetails;

class SaveCardDetails implements OrderProcessorInterface
{
    /**
     * @var SaveAdditionalInformationDetails
     */
    private $saveAdditionalInformationDetails;

    public function __construct(
        SaveAdditionalInformationDetails $saveAdditionalInformationDetails
    ) {
        $this->saveAdditionalInformationDetails = $saveAdditionalInformationDetails;
    }

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

        $this->saveAdditionalInformationDetails->execute($magentoOrder->getPayment(), $details);

        return $response;
    }
}
