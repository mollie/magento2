<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Client\Payments\Processors;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Model\Client\PaymentProcessorInterface;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Service\Mollie\Order\CanUseManualCapture;

class SaveCaptureBeforeDate implements PaymentProcessorInterface
{
    public function __construct(
        private readonly CanUseManualCapture $canUseManualCapture,
    ) {}

    public function process(
        OrderInterface $magentoOrder,
        Payment $molliePayment,
        string $type,
        ProcessTransactionResponse $response,
    ): ?ProcessTransactionResponse {
        if (!$this->canUseManualCapture->execute($magentoOrder)) {
            return $response;
        }

        if ($molliePayment->captureBefore === null) {
            return $response;
        }

        $captureBeforeDate = $molliePayment->captureBefore;
        $magentoOrder->getPayment()->setAdditionalInformation('mollie_capture_before', $captureBeforeDate);

        return $response;
    }
}
