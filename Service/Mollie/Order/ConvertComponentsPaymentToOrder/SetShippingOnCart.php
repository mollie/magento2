<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Order\ConvertComponentsPaymentToOrder;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Address\Rate;
use Magento\Quote\Model\Quote\Address\RateFactory;
use Magento\Tax\Model\Config as TaxConfig;
use Mollie\Api\Resources\Payment;
use stdClass;

class SetShippingOnCart
{
    private const AMOUNT_PRECISION = 0.005;

    public function __construct(
        private readonly RateFactory $rateFactory,
        private readonly TaxConfig $taxConfig,
    ) {}

    public function execute(CartInterface $baseCart, CartInterface $cart, Payment $payment): void
    {
        $this->copyProductsFromBaseCart($baseCart, $cart);

        $hasShipping = false;
        foreach ($payment->lines ?? [] as $line) {
            /** @var stdClass $line */
            if ($line->type == 'shipping_fee') {
                $hasShipping = true;
                $this->addShippingToQuote($baseCart, $cart, $line);
            }
        }

        // The captured amount never included shipping when there is no shipping line,
        // so the order must not charge for it either.
        if (!$hasShipping) {
            $this->setShippingToZero($cart);
        }
    }

    private function copyProductsFromBaseCart(CartInterface $baseCart, CartInterface $cart): void
    {
        foreach ($baseCart->getAllVisibleItems() as $item) {
            $result = $cart->addProduct($item->getProduct(), $item->getBuyRequest());
            if (is_string($result)) {
                throw new LocalizedException(__($result));
            }
        }
    }

    private function addShippingToQuote(CartInterface $baseCart, CartInterface $cart, stdClass $line): void
    {
        $shippingMethod = 'flatrate_flatrate';

        $address = $cart->getShippingAddress();

        /** @var Rate $shippingRate */
        $shippingRate = $this->rateFactory->create();
        $shippingRate->setCode($shippingMethod);
        $shippingRate->setPrice($this->getShippingPrice($baseCart, $cart, $line));
        $shippingRate->setCarrierTitle('Express');
        $shippingRate->setMethodTitle($line->description ?? '');

        $address->setCollectShippingRates(true)
            ->collectShippingRates()
            ->setShippingMethod($shippingMethod);

        $address->getShippingRatesCollection()->removeAllItems();
        $address->addShippingRate($shippingRate);
    }

    private function getShippingPrice(CartInterface $baseCart, CartInterface $cart, stdClass $line): float
    {
        $paidShipping = (float)$line->totalAmount->value;
        if ($this->taxConfig->shippingPriceIncludesTax($cart->getStoreId())) {
            return $paidShipping;
        }

        if (!$this->isShippingOfBaseCart($baseCart, $paidShipping)) {
            return $paidShipping;
        }

        return (float)$baseCart->getShippingAddress()->getShippingAmount();
    }

    private function isShippingOfBaseCart(CartInterface $baseCart, float $paidShipping): bool
    {
        $baseCartShipping = (float)$baseCart->getShippingAddress()->getShippingInclTax();

        return abs($baseCartShipping - $paidShipping) < self::AMOUNT_PRECISION;
    }

    private function setShippingToZero(CartInterface $cart): void
    {
        $address = $cart->getShippingAddress();

        $address->setCollectShippingRates(true)
            ->collectShippingRates();

        if (!$address->getShippingMethod()) {
            $shippingMethod = 'flatrate_flatrate';
            $address->setShippingMethod($shippingMethod);
        }

        foreach ($address->getShippingRatesCollection() as $rate) {
            $rate->setPrice(0);
        }
    }
}
