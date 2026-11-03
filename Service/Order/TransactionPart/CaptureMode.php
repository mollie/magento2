<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\TransactionPart;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Model\Adminhtml\Source\CaptureMode as CaptureModeSource;
use Mollie\Payment\Service\Mollie\Order\CanUseManualCapture;
use Mollie\Payment\Service\Mollie\Order\WhenToCapture;
use Mollie\Payment\Service\Order\TransactionPartInterface;

class CaptureMode implements TransactionPartInterface
{
    public function __construct(
        private CanUseManualCapture $canUseManualCapture,
        private WhenToCapture $whenToCapture,
    ) {
    }

    public function process(OrderInterface $order, array $transaction): array
    {
        $payment = $order->getPayment();

        if (!$this->canUseManualCapture->execute($order)) {
            $payment->setAdditionalInformation(CanUseManualCapture::CAPTURE_MODE, CaptureModeSource::AUTOMATIC);

            return $transaction;
        }

        $payment->setAdditionalInformation(CanUseManualCapture::CAPTURE_MODE, CaptureModeSource::MANUAL);
        $payment->setAdditionalInformation(WhenToCapture::CAPTURE_MOMENT, $this->whenToCapture->forOrder($order));

        $transaction['captureMode'] = CaptureModeSource::MANUAL;

        return $transaction;
    }
}
