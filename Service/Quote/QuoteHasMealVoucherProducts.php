<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Quote;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Mollie\Payment\Config;

class QuoteHasMealVoucherProducts
{
    public function __construct(
        private Config $config
    ) {}

    public function check(CartInterface $cart): bool
    {
        if ($cart->getItems() === null) {
            return false;
        }

        $itemsWithCategories = array_filter($cart->getItems(), function (CartItemInterface $cartItem): bool {
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
