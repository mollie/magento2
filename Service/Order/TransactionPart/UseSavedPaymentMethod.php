<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\TransactionPart;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Order\TransactionPartInterface;

class UseSavedPaymentMethod implements TransactionPartInterface
{
    public function __construct(
        private Config $config
    ) {}

    public function process(OrderInterface $order, array $transaction): array
    {
        if (
            !$this->config->isMagentoVaultEnabled(storeId(storeId($order->getStoreId()))) ||
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

        $transaction['sequenceType'] = 'oneoff';
        $transaction['mandateId'] = $paymentToken->getGatewayToken();

        return $transaction;
    }
}
