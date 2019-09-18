<?php
/**
 * Copyright © 2019 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\PaymentFee\Quote\Address\Total;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Mollie\Payment\Service\Config\PaymentFee as PaymentFeeConfig;

class PaymentFeeTax extends AbstractTotal
{
    /**
     * @var PaymentFeeConfig
     */
    private $paymentFeeConfig;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    public function __construct(
        PaymentFeeConfig $paymentFeeConfig,
        PriceCurrencyInterface $priceCurrency
    ) {
        $this->paymentFeeConfig = $paymentFeeConfig;
        $this->priceCurrency = $priceCurrency;
    }

    public function collect(Quote $quote, ShippingAssignmentInterface $shippingAssignment, Total $total)
    {
        parent::collect($quote, $shippingAssignment, $total);

        if (!$shippingAssignment->getItems() || !$this->paymentFeeConfig->isAvailableForMethod($quote)) {
            return $this;
        }

        $baseAmount = $this->paymentFeeConfig->tax($quote);
        $amount = $this->priceCurrency->convert($baseAmount);

        $total->addTotalAmount('tax', $amount);
        $total->addBaseTotalAmount('tax', $baseAmount);

        $quote->getExtensionAttributes()->setMolliePaymentFeeTax($amount);
        $quote->getExtensionAttributes()->setBaseMolliePaymentFeeTax($baseAmount);

        return $this;
    }
}
