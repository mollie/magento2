<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Tax;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Tax\Model\Calculation;
use Mollie\Payment\Service\Config\PaymentFee;

class TaxCalculate
{
    public function __construct(
        private Calculation $taxCalculation,
        private PaymentFee $config
    ) {}

    public function getTaxFromAmountIncludingTax(CartInterface $cart, $amount)
    {
        $shippingAddress = $cart->getShippingAddress();
        $billingAddress = $cart->getBillingAddress();
        $customerTaxClassId = $cart->getCustomerTaxClassId();
        $storeId = storeId($cart->getStoreId());

        $request = $this->taxCalculation->getRateRequest(
            $shippingAddress,
            $billingAddress,
            $customerTaxClassId,
            $storeId,
        );

        $request->setProductClassId($this->config->getTaxClass($cart));

        $rate = $this->taxCalculation->getRate($request);

        return $this->taxCalculation->calcTaxAmount(
            $amount,
            $rate,
            true,
            false,
        );
    }
}
