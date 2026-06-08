<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Observer\CheckoutAllowGuest;

use Magento\Framework\DataObject;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\Data\CartInterface;
use Mollie\Payment\Service\Quote\CartContainsRecurringProduct;

class ForceCustomerLoginWhenSubscriptionItemInCart implements ObserverInterface
{
    public function __construct(
        private CartContainsRecurringProduct $cartContainsRecurringProduct
    ) {}

    public function execute(Observer $observer): void
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
