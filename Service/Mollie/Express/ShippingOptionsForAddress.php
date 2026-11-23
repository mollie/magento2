<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Express;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use Magento\Quote\Model\Cart\ShippingMethodConverter;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Address\Rate;
use Magento\Quote\Model\Quote\TotalsCollector;
use Mollie\Payment\Helper\General;

/**
 * Calculates the shipping options on the quote in memory, the same way Magento estimates them, without saving the
 * address. The product page quote does not exist in the database, so the estimation API cannot be used.
 */
class ShippingOptionsForAddress
{
    private const REFERENCE_SEPARATOR = '_';
    private const DESCRIPTION_SEPARATOR = ' - ';

    public function __construct(
        private readonly TotalsCollector $totalsCollector,
        private readonly ShippingMethodConverter $shippingMethodConverter,
        private readonly General $mollieHelper,
    ) {}

    /**
     * @param array<string, mixed> $address The partial address Mollie sends: postalCode, city, region and country.
     * @return array<int, array{reference: string, description: string, amount: array<string, string|null>}>
     */
    public function execute(CartInterface $cart, array $address): array
    {
        /** @var Quote $cart */
        if ($cart->isVirtual()) {
            return [];
        }

        $currency = (string)$cart->getQuoteCurrencyCode();

        return array_values(array_map(
            fn (ShippingMethodInterface $method): array => $this->toOption($method, $currency),
            array_filter(
                array_map(
                    fn (Rate $rate): ShippingMethodInterface => $this->shippingMethodConverter->modelToDataObject(
                        $rate,
                        $currency,
                    ),
                    $this->collectRates($cart, $address),
                ),
                static fn (ShippingMethodInterface $method): bool => (bool)$method->getAvailable(),
            ),
        ));
    }

    /**
     * @param array<string, mixed> $address
     * @return Rate[]
     */
    private function collectRates(Quote $cart, array $address): array
    {
        $shippingAddress = $cart->getShippingAddress();
        $shippingAddress->addData([
            'country_id' => strtoupper((string)($address['country'] ?? '')),
            'postcode' => (string)($address['postalCode'] ?? ''),
            'city' => (string)($address['city'] ?? ''),
            'region' => (string)($address['region'] ?? ''),
        ]);
        $shippingAddress->setCollectShippingRates(true);

        $this->totalsCollector->collectAddressTotals($cart, $shippingAddress);

        return array_merge([], ...array_values($shippingAddress->getGroupedAllShippingRates()));
    }

    /**
     * @return array{reference: string, description: string, amount: array<string, string|null>}
     */
    private function toOption(ShippingMethodInterface $method, string $currency): array
    {
        return [
            'reference' => $method->getCarrierCode() . self::REFERENCE_SEPARATOR . $method->getMethodCode(),
            'description' => $this->getDescription($method),
            'amount' => $this->mollieHelper->getAmountArray($currency, (float)$method->getPriceInclTax()),
        ];
    }

    private function getDescription(ShippingMethodInterface $method): string
    {
        return implode(self::DESCRIPTION_SEPARATOR, array_filter(
            [(string)$method->getCarrierTitle(), (string)$method->getMethodTitle()],
            static fn (string $title): bool => $title !== '',
        ));
    }
}
