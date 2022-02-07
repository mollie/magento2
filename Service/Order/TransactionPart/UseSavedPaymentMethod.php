<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order\TransactionPart;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Client\Orders;
use Mollie\Payment\Model\Client\Payments;
use Mollie\Payment\Service\Order\TransactionPartInterface;

class UseSavedPaymentMethod implements TransactionPartInterface
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
        if (
            !$this->config->isMagentoVaultEnabled($order->getStoreId()) ||
            !$order->getPayment() ||
            !$order->getPayment()->getExtensionAttributes() ||
            !$order->getPayment()->getExtensionAttributes()->getVaultPaymentToken()
        ) {
            return $transaction;
        }

        $paymentToken = $order->getPayment()->getExtensionAttributes()->getVaultPaymentToken();
        if ($order->getPayment()->getMethod() != $paymentToken->getPaymentMethodCode()) {
            return $transaction;
        }

        if ($apiMethod == Payments::CHECKOUT_TYPE) {
            $transaction['sequenceType'] = 'oneoff';
            $transaction['mandateId'] = $paymentToken->getGatewayToken();
        }

        if ($apiMethod == Orders::CHECKOUT_TYPE) {
            $transaction['payment']['sequenceType'] = 'oneoff';
            $transaction['payment']['mandateId'] = $paymentToken->getGatewayToken();
        }

        return $transaction;
    }
}
