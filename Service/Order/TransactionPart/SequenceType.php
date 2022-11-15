<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order\TransactionPart;

use Magento\Customer\Model\Session;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Vault\Model\Ui\VaultConfigProvider;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Client\Orders;
use Mollie\Payment\Model\Client\Payments;
use Mollie\Payment\Service\Order\OrderContainsSubscriptionProduct;
use Mollie\Payment\Service\Order\TransactionPartInterface;

class SequenceType implements TransactionPartInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var OrderContainsSubscriptionProduct
     */
    private $orderContainsSubscriptionProduct;

    /**
     * @var Session
     */
    private $customerSession;

    public function __construct(
        Config $config,
        OrderContainsSubscriptionProduct $orderContainsSubscriptionProduct,
        Session $customerSession
    ) {
        $this->config = $config;
        $this->orderContainsSubscriptionProduct = $orderContainsSubscriptionProduct;
        $this->customerSession = $customerSession;
    }

    public function process(OrderInterface $order, $apiMethod, array $transaction): array
    {
        if (!$this->shouldAddSequenceType($order)) {
            return $transaction;
        }

        if ($apiMethod == Payments::CHECKOUT_TYPE) {
            $transaction['sequenceType'] = 'first';
        }

        if ($apiMethod == Orders::CHECKOUT_TYPE) {
            $transaction['payment']['sequenceType'] = 'first';
        }

        return $transaction;
    }

    private function shouldAddSequenceType(OrderInterface $order): bool
    {
        if ($this->orderContainsSubscriptionProduct->check($order)) {
            return true;
        }

        if (!$order->getPayment() || !$this->customerSession->isLoggedIn()) {
            return false;
        }

        if ($this->config->isMagentoVaultEnabled($order->getStoreId()) &&
            $order->getPayment()->getAdditionalInformation(VaultConfigProvider::IS_ACTIVE_CODE) &&
            $order->getPayment()->getMethod() == 'mollie_methods_creditcard'
        ) {
            return true;
        }

        return false;
    }
}
