<?php

namespace Mollie\Payment\Service\Mollie\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Methods\Creditcard;

class CanRegisterCaptureNotification
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

    public function execute(OrderInterface $order): bool
    {
        if ($this->config->useManualCapture($order->getStoreId()) &&
            $order->getPayment()->getMethod() == Creditcard::CODE
        ) {
            return false;
        }

        return true;
    }
}
