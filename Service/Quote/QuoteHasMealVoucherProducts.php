<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Quote;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Mollie\Payment\Config;

class QuoteHasMealVoucherProducts
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

    public function check(CartInterface $cart): bool
    {
        if ($cart->getItems() === null) {
            return false;
        }

        $itemsWithCategories = array_filter($cart->getItems(), function (CartItemInterface $cartItem) {
            $category = $this->getProductCategory($cartItem->getProduct());

            return $category && $category != 'none';
        });

        return count($itemsWithCategories) == count($cart->getItems());
    }

    private function getProductCategory(ProductInterface $product)
    {
        $attributeCode = $this->config->getVoucherCustomAttribute();
        $value = $product->getAttributeText($attributeCode);
        if ($value) {
            return $value;
        }

        return $product->getData($attributeCode);
    }
}
