<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\PaymentFee\Quote\Address\Total;

use Magento\Customer\Api\AccountManagementInterface as CustomerAccountManagement;
use Magento\Customer\Api\Data\AddressInterfaceFactory as CustomerAddressFactory;
use Magento\Customer\Api\Data\RegionInterfaceFactory as CustomerAddressRegionFactory;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Quote\Api\Data\ShippingAssignmentInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Total;
use Magento\Quote\Model\Quote\Address\Total\AbstractTotal;
use Magento\Tax\Api\Data\QuoteDetailsInterfaceFactory;
use Magento\Tax\Api\Data\QuoteDetailsItemExtensionInterfaceFactory;
use Magento\Tax\Api\Data\QuoteDetailsItemInterfaceFactory;
use Magento\Tax\Api\Data\TaxClassKeyInterface;
use Magento\Tax\Api\Data\TaxClassKeyInterfaceFactory;
use Magento\Tax\Api\TaxCalculationInterface;
use Magento\Tax\Helper\Data as TaxHelper;
use Magento\Tax\Model\Config as TaxConfig;
use Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector;
use Mollie\Payment\Config;
use Mollie\Payment\Exceptions\UnknownPaymentFeeType;
use Mollie\Payment\Service\PaymentFee\Calculate;
use Mollie\Payment\Service\PaymentFee\Result;

class PaymentFeeTax extends CommonTaxCollector
{
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
        TaxConfig $taxConfig,
        TaxCalculationInterface $taxCalculationService,
        QuoteDetailsInterfaceFactory $quoteDetailsDataObjectFactory,
        QuoteDetailsItemInterfaceFactory $quoteDetailsItemDataObjectFactory,
        TaxClassKeyInterfaceFactory $taxClassKeyDataObjectFactory,
        CustomerAddressFactory $customerAddressFactory,
        CustomerAddressRegionFactory $customerAddressRegionFactory,
        Calculate $calculate,
        PriceCurrencyInterface $priceCurrency,
        Config $config,
        ?TaxHelper $taxHelper = null,
        ?QuoteDetailsItemExtensionInterfaceFactory $quoteDetailsItemExtensionInterfaceFactory = null,
        ?CustomerAccountManagement $customerAccountManagement = null
    ) {
        $parent = new \ReflectionClass(parent::class);
        $parentConstructor = $parent->getConstructor();

        // The parent call fails when running setup:di:compile in 2.4.3 and lower due to an extra parameter.
        if ($parentConstructor->getNumberOfParameters() == 9) {
            // @phpstan-ignore-next-line
            parent::__construct(
                $taxConfig,
                $taxCalculationService,
                $quoteDetailsDataObjectFactory,
                $quoteDetailsItemDataObjectFactory,
                $taxClassKeyDataObjectFactory,
                $customerAddressFactory,
                $customerAddressRegionFactory,
                $taxHelper,
                $quoteDetailsItemExtensionInterfaceFactory
            );
        } else {
            // @phpstan-ignore-next-line
            parent::__construct(
                $taxConfig,
                $taxCalculationService,
                $quoteDetailsDataObjectFactory,
                $quoteDetailsItemDataObjectFactory,
                $taxClassKeyDataObjectFactory,
                $customerAddressFactory,
                $customerAddressRegionFactory,
                $taxHelper,
                $quoteDetailsItemExtensionInterfaceFactory,
                $customerAccountManagement
            );
        }

        $this->calculate = $calculate;
        $this->priceCurrency = $priceCurrency;
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
        $amount = $this->priceCurrency->convert($result->getRoundedTaxAmount());

        $total->addTotalAmount('tax', $amount);
        $total->addBaseTotalAmount('tax', $result->getRoundedTaxAmount());

        $this->addAssociatedTaxable($shippingAssignment, $result, $quote);

        $feeDataObject = $this->quoteDetailsItemDataObjectFactory->create()
            ->setType('mollie_payment_fee')
            ->setCode('mollie_payment_fee')
            ->setQuantity(1);

        $feeDataObject->setUnitPrice($result->getRoundedAmount());
        $feeDataObject->setTaxClassKey(
            $this->taxClassKeyDataObjectFactory->create()
                ->setType(TaxClassKeyInterface::TYPE_ID)
                ->setValue(4)
        );
        $feeDataObject->setIsTaxIncluded(true);

        $quoteDetails = $this->prepareQuoteDetails($shippingAssignment, [$feeDataObject]);

        $this->taxCalculationService->calculateTax($quoteDetails, $quote->getStoreId());

        parent::collect($quote, $shippingAssignment, $total);

        $extensionAttributes = $quote->getExtensionAttributes();
        if (!$extensionAttributes) {
            return $this;
        }

        $extensionAttributes->setMolliePaymentFeeTax($amount);
        $extensionAttributes->setBaseMolliePaymentFeeTax($result->getRoundedTaxAmount());

        return $this;
    }

    /**
     * @param ShippingAssignmentInterface $shippingAssignment
     * @param Result $result
     * @param Quote $quote
     */
    private function addAssociatedTaxable(ShippingAssignmentInterface $shippingAssignment, Result $result, Quote $quote)
    {
        $fullAmount = $this->priceCurrency->convert($result->getRoundedAmount());

        $address = $shippingAssignment->getShipping()->getAddress();
        $associatedTaxables = $address->getAssociatedTaxables();

        $method = $quote->getPayment()->getMethod();
        $taxClass = $this->config->paymentSurchargeTaxClass($method, $quote->getStoreId());

        $associatedTaxables[] = [
            CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_TYPE => 'mollie_payment_fee_tax',
            CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_CODE => 'mollie_payment_fee_tax',
            CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_UNIT_PRICE => $fullAmount,
            CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_BASE_UNIT_PRICE => $result->getRoundedAmount(),
            CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_QUANTITY => 1,
            CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_TAX_CLASS_ID => $taxClass,
            CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_PRICE_INCLUDES_TAX => false,
            CommonTaxCollector::KEY_ASSOCIATED_TAXABLE_ASSOCIATION_ITEM_CODE => null,
        ];

        $address->setAssociatedTaxables($associatedTaxables);
    }
}
