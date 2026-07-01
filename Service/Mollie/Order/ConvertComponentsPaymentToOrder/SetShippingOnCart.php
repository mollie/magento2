<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Mollie\Order\ConvertComponentsPaymentToOrder;

use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Address\Rate;
use Magento\Quote\Model\Quote\Address\RateFactory;
use Mollie\Api\Resources\Payment;
use stdClass;

class SetShippingOnCart
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly RateFactory $rateFactory,
    ) {}

    public function execute(CartInterface $cart, Payment $payment): void
    {
        $hasShipping = false;
        foreach ($payment->lines ?? [] as $line) {
            /** @var stdClass $line */
            if ($line->type == 'physical') {
                $this->addProductToCart($line, $cart);
            }

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

    private function addProductToCart(stdClass $line, CartInterface $cart): void
    {
        if (!preg_match('/^\[([^\]]+)\]/', $line->description, $matches)) {
            throw new LocalizedException(__('Unable to extract SKU from description: %1', $line->description));
        }

        $sku = $matches[1];
        $product = $this->productRepository->get($sku);

        $product->setPrice($line->unitPrice->value);
        $cart->addProduct(
            $product,
            intval($line->quantity)
        );
    }

    private function addShippingToQuote(CartInterface $cart, stdClass $line): void
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
