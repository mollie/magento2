<?php

namespace Mollie\Payment\Service\Order\TransactionPart;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Client\Orders;
use Mollie\Payment\Model\Client\Payments;
use Mollie\Payment\Service\Order\TransactionPartInterface;

class CaptureMode implements TransactionPartInterface
{
    /**
     * @var Config
     */
    private $config;

    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    public function process(OrderInterface $order, $apiMethod, array $transaction)
    {
        if ($apiMethod == Orders::CHECKOUT_TYPE) {
            return $transaction;
        }

        if ($order->getPayment()->getMethod() != 'mollie_methods_creditcard' ||
            !$this->config->useManualCapture($order->getStoreId())
        ) {
            return $transaction;
        }

        $transaction['captureMode'] = 'manual';

        return $transaction;
    }
}
