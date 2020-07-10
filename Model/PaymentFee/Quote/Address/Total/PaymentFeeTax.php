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
use Mollie\Payment\Config;
use Mollie\Payment\Exceptions\UnknownPaymentFeeType;
use Mollie\Payment\Service\Config\PaymentFee as PaymentFeeConfig;
use Mollie\Payment\Service\PaymentFee\Calculate;
use Mollie\Payment\Service\PaymentFee\Result;

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

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        PaymentFeeConfig $paymentFeeConfig,
        PriceCurrencyInterface $priceCurrency,
        Calculate $calculate,
        Config $config
    ) {
        $this->paymentFeeConfig = $paymentFeeConfig;
        $this->priceCurrency = $priceCurrency;
        $this->calculate = $calculate;
        $this->config = $config;
    }

    /**
     * @param Quote $quote
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Total $total
     * @return $this|AbstractTotal
     * @throws UnknownPaymentFeeType
     */
    public function collect(Quote $quote, ShippingAssignmentInterface $shippingAssignment, Total $total)
    {
        if (!$shippingAssignment->getItems()) {
            parent::collect($quote, $shippingAssignment, $total);
            return $this;
        }

        $result = $this->calculate->forCart($quote, $total);
        $amount = $this->priceCurrency->convert($result->getTaxAmount());

        $total->addTotalAmount('tax', $amount);
        $total->addBaseTotalAmount('tax', $result->getTaxAmount());

        $this->addAssociatedTaxable($shippingAssignment, $result, $quote);

        parent::collect($quote, $shippingAssignment, $total);

        $extensionAttributes = $quote->getExtensionAttributes();

        if (!$extensionAttributes) {
            return $this;
        }

        $extensionAttributes->setMolliePaymentFeeTax($amount);
        $extensionAttributes->setBaseMolliePaymentFeeTax($result->getTaxAmount());

        return $this;
    }

    /**
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Result $result
     * @param Quote $quote
     */
    private function addAssociatedTaxable(ShippingAssignmentInterface $shippingAssignment, Result $result, Quote $quote): void
    {
        $fullAmount = $this->priceCurrency->convert($result->getAmount());

        $address = $shippingAssignment->getShipping()->getAddress();
        $associatedTaxables = $address->getAssociatedTaxables();

        $method = $quote->getPayment()->getMethod();
        $taxClass = $this->config->paymentSurchargeTaxClass($method, $quote->getStoreId());

        $associatedTaxables[] = [
            CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_TYPE => 'mollie_payment_fee_tax',
            CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_CODE => 'mollie_payment_fee_tax',
            CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_UNIT_PRICE => $fullAmount,
            CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_BASE_UNIT_PRICE => $result->getAmount(),
            CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_QUANTITY => 1,
            CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_TAX_CLASS_ID => $taxClass,
            CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_PRICE_INCLUDES_TAX => false,
            CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_ASSOCIATION_ITEM_CODE => null,
        ];

        $address->setAssociatedTaxables($associatedTaxables);
    }
}
