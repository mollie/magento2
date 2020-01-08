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
use Mollie\Payment\Service\PaymentFee\Calculate;

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
        $amount = $this->priceCurrency->convert($result->getTaxAmount());

        $total->addTotalAmount('tax', $amount);
        $total->addBaseTotalAmount('tax', $result->getTaxAmount());

        $extensionAttributes = $quote->getExtensionAttributes();

        if (!$extensionAttributes) {
            return $this;
        }

        $extensionAttributes->setMolliePaymentFeeTax($amount);
        $extensionAttributes->setBaseMolliePaymentFeeTax($result->getTaxAmount());

        return $this;
    }
}
