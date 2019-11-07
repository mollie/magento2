<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\PaymentFee\Quote\Address\Total;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Mollie\Payment\Service\Config\PaymentFee as PaymentFeeConfig;

class PaymentFee extends AbstractTotal
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

    /**
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return $this|AbstractTotal
     */
    public function collect(Quote $quote, ShippingAssignmentInterface $shippingAssignment, Total $total)
    {
        parent::collect($quote, $shippingAssignment, $total);

        if (!$shippingAssignment->getItems() || !$this->paymentFeeConfig->isAvailableForMethod($quote)) {
            return $this;
        }

        $baseAmount = $this->paymentFeeConfig->excludingTax($quote);
        $amount = $this->priceCurrency->convert($baseAmount);

        $total->setTotalAmount('mollie_payment_fee', $amount);
        $total->setBaseTotalAmount('mollie_payment_fee', $baseAmount);

        $attributes = $quote->getExtensionAttributes();

        if (!$attributes) {
            return $this;
        }

        $attributes->setMolliePaymentFee($amount);
        $attributes->setBaseMolliePaymentFee($amount);

        return $this;
    }

    /**
     * @param Quote $quote
     * @param Total $total
     * @return array
     */
    public function fetch(Quote $quote, Total $total)
    {
        if (!$this->paymentFeeConfig->isAvailableForMethod($quote)) {
            return [];
        }

        return [
            'code' => 'mollie_payment_fee',
            'title' => __('Payment Fee'),
            'value' => $this->paymentFeeConfig->includingTax($quote),
        ];
    }

    /**
     * @return \Magento\Framework\Phrase
     */
    public function getLabel()
    {
        return __('Payment Fee');
    }
}
