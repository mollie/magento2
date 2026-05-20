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

class MandateId implements TransactionPartInterface
{
    public function __construct(
        private Config $config,
    ) {}

    public function process(OrderInterface $order, array $transaction): array
    {
        $storeId = storeId($order->getStoreId());
        if (!$this->config->creditcardEnableCustomersApi($storeId)) {
            return $transaction;
        }

        $info = $order->getPayment()->getAdditionalInformation();
        if (($info['mollie_mandate_id'] ?? '') === '') {
            return $transaction;
        }

        $transaction['mandateId'] = $info['mollie_mandate_id'];

        return $transaction;
    }
}
