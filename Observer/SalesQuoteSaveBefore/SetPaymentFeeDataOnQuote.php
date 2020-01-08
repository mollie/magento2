<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Observer\SalesQuoteSaveBefore;


use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\Data\CartInterface;

class SetPaymentFeeDataOnQuote implements ObserverInterface
{
    /**
     * @inheritDoc
     */
    public function execute(Observer $observer)
    {
        /** @var CartInterface $quote */
        $quote = $observer->getData('quote');

        $extensionAttributes = $quote->getExtensionAttributes();
        if (!$extensionAttributes) {
            return;
        }

        $quote->setData('mollie_payment_fee', $extensionAttributes->getMolliePaymentFee());
        $quote->setData('base_mollie_payment_fee', $extensionAttributes->getBaseMolliePaymentFee());
        $quote->setData('mollie_payment_fee_tax', $extensionAttributes->getMolliePaymentFeeTax());
        $quote->setData('base_mollie_payment_fee_tax', $extensionAttributes->getBaseMolliePaymentFeeTax());
    }
}
