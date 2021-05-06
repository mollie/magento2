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
use Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector;
use Mollie\Payment\Service\Config\PaymentFee as PaymentFeeConfig;
use Mollie\Payment\Service\PaymentFee\Calculate;

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

    /**
     * @var Calculate
     */
    private $calculate;

    public function __construct(
        PaymentFeeConfig $paymentFeeConfig,
        PriceCurrencyInterface $priceCurrency,
        Calculate $calculate
    ) {
        $this->paymentFeeConfig = $paymentFeeConfig;
        $this->priceCurrency = $priceCurrency;
        $this->calculate = $calculate;
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

        if (!$shippingAssignment->getItems()) {
            return $this;
        }

        $result = $this->calculate->forCart($quote, $total);
        $amount = $this->priceCurrency->convert($result->getRoundedAmount());

        $total->setTotalAmount('mollie_payment_fee', $amount);
        $total->setBaseTotalAmount('mollie_payment_fee', $result->getRoundedAmount());

        $attributes = $quote->getExtensionAttributes();

        if (!$attributes) {
            return $this;
        }

        $attributes->setMolliePaymentFee($amount);
        $attributes->setBaseMolliePaymentFee($result->getRoundedAmount());

        return $this;
    }

    /**
     * @param Quote $quote
     * @param Total $total
     * @return array
     */
    public function fetch(Quote $quote, Total $total)
    {
        if (!$this->paymentFeeConfig->isAvailableForMethod($quote) || !$quote->getExtensionAttributes()) {
            return [];
        }

        $extensionAttributes = $quote->getExtensionAttributes();

        return [
            'code' => 'mollie_payment_fee',
            'title' => __('Payment Fee'),
            'value' => $extensionAttributes->getMolliePaymentFee() + $extensionAttributes->getMolliePaymentFeeTax(),
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
