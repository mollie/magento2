<?php

declare(strict_types=1);

namespace Mollie\Payment\Model\PaymentFee\Order\Pdf\Total;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Sales\Model\Order\Pdf\Total\DefaultTotal;
use Magento\Tax\Helper\Data;
use Magento\Tax\Model\Calculation;
use Magento\Tax\Model\ResourceModel\Sales\Order\Tax\CollectionFactory;

/**
 * @method getSource();
 * @method int|null getFontSize();
 */
class PaymentFee extends DefaultTotal
{
    public function __construct(
        Data $taxHelper,
        Calculation $taxCalculation,
        CollectionFactory $ordersFactory,
        private PriceCurrencyInterface $currency,
        array $data = [],
    ) {
        parent::__construct($taxHelper, $taxCalculation, $ordersFactory, $data);
    }

    public function getTotalsForDisplay()
    {
        $source = $this->getSource();
        $amount = $source->getMolliePaymentFee() + $source->getMolliePaymentFeeTax();
        $sourceDataCurrency = $source->getData('order_currency_code') ?: null;

        if (!$amount) {
            return [];
        }

        return [
            [
                'amount' => $this->currency->format(
                    $amount,
                    false,
                    PriceCurrencyInterface::DEFAULT_PRECISION,
                    null,
                    $sourceDataCurrency,
                ),
                'label' => __('Payment Fee'),
                'font_size' => $this->getFontSize() ? $this->getFontSize() : 7,
            ],
        ];
    }
}
