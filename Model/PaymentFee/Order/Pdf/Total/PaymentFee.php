<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

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
    /**
     * @var PriceCurrencyInterface
     */
    private $currency;

    public function __construct(
        Data $taxHelper,
        Calculation $taxCalculation,
        CollectionFactory $ordersFactory,
        PriceCurrencyInterface $currency,
        array $data = []
    ) {
        parent::__construct($taxHelper, $taxCalculation, $ordersFactory, $data);

        $this->currency = $currency;
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
                    \Magento\Framework\Pricing\PriceCurrencyInterface::DEFAULT_PRECISION,
                    null,
                    $sourceDataCurrency
                ),
                'label' => __('Payment Fee'),
                'font_size' => $this->getFontSize() ? $this->getFontSize() : 7,
            ],
        ];
    }
}
