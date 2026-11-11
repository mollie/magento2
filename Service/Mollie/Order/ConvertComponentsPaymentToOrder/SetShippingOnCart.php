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
use Mollie\Api\Resources\Payment;
use stdClass;

class SetShippingOnCart
{
    public function __construct(
        private readonly RateFactory $rateFactory,
    ) {}

    public function execute(CartInterface $baseCart, CartInterface $cart, Payment $payment): void
    {
        $this->copyProductsFromBaseCart($baseCart, $cart);

        $hasShipping = false;
        foreach ($payment->lines ?? [] as $line) {
            /** @var stdClass $line */
            if ($line->type == 'shipping_fee') {
                $hasShipping = true;
                $this->addShippingToQuote($cart, $line);
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

    private function addShippingToQuote(CartInterface $cart, stdClass $line): void
    {
        if ($this->useSelectedShippingMethod($cart, $line)) {
            return;
        }

        $this->addExpressShippingRate($cart, $line);
    }

    /**
     * The reference is the shipping method the customer picked from the options the shipping options callback
     * returned. Keep the price Mollie charged, as the rate may have changed since.
     */
    private function useSelectedShippingMethod(CartInterface $cart, stdClass $line): bool
    {
        $reference = $line->reference ?? null;
        if (!is_string($reference) || $reference === '') {
            return false;
        }

        $address = $cart->getShippingAddress();
        $address->setCollectShippingRates(true)->collectShippingRates();

        $rate = $address->getShippingRateByCode($reference);
        if ($rate === false) {
            return false;
        }

        $rate->setPrice($line->totalAmount->value);
        $address->setShippingMethod($reference);

        return true;
    }

    private function addExpressShippingRate(CartInterface $cart, stdClass $line): void
    {
        $shippingMethod = 'flatrate_flatrate';

        $amount = $line->totalAmount->value;
        $address = $cart->getShippingAddress();

        /** @var Rate $shippingRate */
        $shippingRate = $this->rateFactory->create();
        $shippingRate->setCode($shippingMethod);
        $shippingRate->setPrice($amount);
        $shippingRate->setCarrierTitle('Express');
        $shippingRate->setMethodTitle($line->description ?? '');

        $address->setCollectShippingRates(true)
            ->collectShippingRates()
            ->setShippingMethod($shippingMethod);

        $address->getShippingRatesCollection()->removeAllItems();
        $address->addShippingRate($shippingRate);
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
