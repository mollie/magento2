<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order\TransactionPart;

use Magento\Framework\Module\Manager;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\GraphQL\Resolver\Checkout\CreateMollieTransaction;
use Mollie\Payment\Model\Client\Orders;
use Mollie\Payment\Model\Client\Payments;
use Mollie\Payment\Service\Order\TransactionPartInterface;

class Issuer implements TransactionPartInterface
{
    /**
     * @var Manager
     */
    private $moduleManager;

    public function __construct(
        Manager $moduleManager
    ) {
        $this->moduleManager = $moduleManager;
    }

    /**
     * @inheritDoc
     */
    public function process(OrderInterface $order, $apiMethod, array $transaction)
    {
        $value = $this->getSelectedIssuer($order);
        if ($value && $apiMethod == Orders::CHECKOUT_TYPE) {
            $transaction['payment']['issuer'] = $value;
        }

        if ($value && $apiMethod == Payments::CHECKOUT_TYPE) {
            $transaction['issuer'] = $value;
        }

        return $transaction;
    }

    private function getSelectedIssuer(OrderInterface $order)
    {
        $additionalData = $order->getPayment()->getAdditionalInformation();

        if (isset($additionalData['selected_issuer'])) {
            return $additionalData['selected_issuer'];
        }
        
        if ($this->moduleManager->isEnabled('Magento_QuoteGraphQl')) {
            return CreateMollieTransaction::getIssuer();
        }

        return null;
    }
}