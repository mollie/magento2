<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Session;

use Magento\Quote\Api\Data\AddressInterface;
use Magento\Tax\Model\Sales\Total\Quote\CommonTaxCollector;
use Mollie\Payment\Helper\General;

class LineVat
{
    private const PERCENT = 100;

    public function __construct(
        private readonly General $mollieHelper,
    ) {}

    /**
     * @return array{vatRate: string, vatAmount: array{currency: ?string, value: string}}
     */
    public function forLine(?string $currency, float $totalAmount, float $vatRate): array
    {
        $sentVatRate = round($vatRate, 2);

        return [
            'vatRate' => sprintf('%.2f', $sentVatRate),
            'vatAmount' => $this->mollieHelper->getAmountArray(
                $currency,
                round($totalAmount * ($sentVatRate / (self::PERCENT + $sentVatRate)), 2),
            ),
        ];
    }

    public function getShippingVatRate(AddressInterface $address): float
    {
        $appliedTaxes = $address->getItemsAppliedTaxes()[CommonTaxCollector::ITEM_CODE_SHIPPING] ?? [];
        if ($appliedTaxes !== []) {
            return (float)array_sum(array_column($appliedTaxes, 'percent'));
        }

        $shippingAmount = (float)$address->getShippingAmount();
        if ($shippingAmount === 0.0) {
            return 0.0;
        }

        return (float)$address->getShippingTaxAmount() / $shippingAmount * self::PERCENT;
    }
}
