<?php

namespace Mollie\Payment\Service\Order\TransactionPart;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Model\Client\Orders;
use Mollie\Payment\Service\Mollie\Order\CanUseManualCapture;
use Mollie\Payment\Service\Order\TransactionPartInterface;

class CaptureMode implements TransactionPartInterface
{
    /**
     * @var CanUseManualCapture
     */
    private $canUseManualCapture;

    public function __construct(
        CanUseManualCapture $canUseManualCapture
    ) {
        $this->canUseManualCapture = $canUseManualCapture;
    }

    public function process(OrderInterface $order, $apiMethod, array $transaction)
    {
        if ($apiMethod == Orders::CHECKOUT_TYPE) {
            return $transaction;
        }

        if (!$this->canUseManualCapture->execute($order)) {
            return $transaction;
        }

        $transaction['captureMode'] = 'manual';

        return $transaction;
    }
}
