<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Observer\CheckoutAllowGuest;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\Data\CartInterface;
use Mollie\Payment\Service\Quote\CartContainsRecurringProduct;

class ForceCustomerLoginWhenSubscriptionItemInCart implements ObserverInterface
{
    /**
     * @var CartContainsRecurringProduct
     */
    private $cartContainsRecurringProduct;

    public function __construct(
        CartContainsRecurringProduct $cartContainsRecurringProduct
    ) {
        $this->cartContainsRecurringProduct = $cartContainsRecurringProduct;
    }

    public function execute(Observer $observer)
    {
        /** @var CartInterface $cart */
        $cart = $observer->getData('quote');

        if ($this->cartContainsRecurringProduct->execute($cart)) {
            /** @var DataObject $result */
            $result = $observer->getData('result');
            $result->setData('is_allowed', false);
        }
    }
}
