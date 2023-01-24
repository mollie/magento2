<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Plugin\Sales\Cart;

use Magento\Framework\Api\SearchResultsInterface;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartExtensionInterfaceFactory;
use Magento\Quote\Api\Data\CartInterface;

class AddPaymentFeeToCart
{
    /**
     * @var CartExtensionInterfaceFactory
     */
    private $factory;

    /**
     * @var int[]
     */
    private $cartsProcessed = [];

    public function __construct(
        CartExtensionInterfaceFactory $factory
    ) {
        $this->factory = $factory;
    }

    public function afterGet(CartRepositoryInterface $subject, CartInterface $result)
    {
        $result = $this->processCart($result);

        return $result;
    }

    public function afterGetList(CartRepositoryInterface $subject, SearchResultsInterface $result)
    {
        $items = $result->getItems();
        foreach ($items as $id => $item) {
            $items[$id] = $this->processCart($item);
        }

        $result->setItems($items);

        return $result;
    }

    /**
     * @param CartInterface $cart
     * @return CartInterface
     */
    private function processCart(CartInterface $cart)
    {
        // This code sometimes collides with other extensions. When the cart is loaded during the totals processing,
        // it will overwrite the calculated values with the values from the database. This code will prevent this.
        if (in_array($cart->getId(), $this->cartsProcessed)) {
            return $cart;
        }

        $extensionAttributes = $cart->getExtensionAttributes() ? $cart->getExtensionAttributes() : $this->factory->create();

        $extensionAttributes->setMolliePaymentFee($cart->getData('mollie_payment_fee'));
        $extensionAttributes->setBaseMolliePaymentFee($cart->getData('base_mollie_payment_fee'));
        $extensionAttributes->setMolliePaymentFeeTax($cart->getData('mollie_payment_fee_tax'));
        $extensionAttributes->setBaseMolliePaymentFeeTax($cart->getData('base_mollie_payment_fee_tax'));

        $cart->setExtensionAttributes($extensionAttributes);

        $this->cartsProcessed[] = $cart->getId();
        return $cart;
    }
}
