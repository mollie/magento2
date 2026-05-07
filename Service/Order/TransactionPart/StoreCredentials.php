<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order\TransactionPart;

use Magento\Customer\Model\Session;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Order\TransactionPartInterface;

class StoreCredentials implements TransactionPartInterface
{
    public function __construct(
        private Config $config,
        private Session $customerSession,
    ) {}

    public function process(OrderInterface $order, array $transaction): array
    {
        $payment = $order->getPayment();
        if (!$payment || $payment->getMethod() !== 'mollie_methods_creditcard') {
            return $transaction;
        }

        if (!$this->customerSession->isLoggedIn()) {
            return $transaction;
        }

        $storeId = storeId($order->getStoreId());
        if (!$this->config->creditcardEnableCustomersApi($storeId)) {
            return $transaction;
        }

        $info = $payment->getAdditionalInformation();
        if (($info['mollie_save_card'] ?? null) !== true || ($info['mollie_mandate_id'] ?? '') !== '') {
            return $transaction;
        }

        $transaction['storeCredentials'] = true;

        return $transaction;
    }
}
