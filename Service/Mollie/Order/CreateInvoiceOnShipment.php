<?php

namespace Mollie\Payment\Service\Mollie\Order;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Config;

class CreateInvoiceOnShipment
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
        $methodCode = $order->getPayment()->getMethod();
        if (in_array($methodCode, [
            'mollie_methods_billie',
            'mollie_methods_klarna',
            'mollie_methods_klarnapaylater',
            'mollie_methods_klarnapaynow',
            'mollie_methods_klarnasliceit',
            'mollie_methods_in3',
        ])) {
            return true;
        }

        $transactionId = $order->getMollieTransactionId() ?? '';
        $api = substr($transactionId, 0, 4) == 'ord_' ? 'orders' : 'payments';
        if ($methodCode == 'mollie_methods_creditcard' &&
            $this->config->useManualCapture($order->getStoreId()) &&
            $api == 'payments'
        ) {
            return false;
        }

        return false;
    }
}
