<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Magento\Quote\Api\Data\TotalsInterface;
use Mollie\Api\Http\Data\Money;
use Mollie\Api\Resources\Session;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Service\Mollie\Api\CreateSessionRequest;
use Mollie\Payment\Service\Order\Transaction;
use Mollie\Payment\Service\PaymentToken\PaymentTokenForQuote;

class CreateSession
{
    private const GUEST_EMAIL_KEY = 'mollie_guest_email';
    private const CUSTOMER_DETAIL_EMAIL = 'email';
    private const CUSTOMER_DETAIL_BILLING_ADDRESS = 'billing-address';
    private const CUSTOMER_DETAIL_SHIPPING_ADDRESS = 'shipping-address';

    private CartInterface $cart;
    private TotalsInterface $totals;

    public function __construct(
        private readonly MollieApiClient $mollieApiClient,
        private readonly PaymentTokenForQuote $paymentTokenForQuote,
        private readonly Transaction $transaction,
        private readonly UrlInterface $urlBuilder,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly General $mollieHelper,
        private readonly CartTotalRepositoryInterface $cartTotalRepository,
    ) {
    }

    public function execute(CartInterface $cart, bool $isExpressCheckout = true): string
    {
        $this->cart = $cart;
        $this->totals = $this->cartTotalRepository->get($cart->getId());

        $mollie = $this->mollieApiClient->loadByStore($cart->getStoreId());
        $paymentToken = $this->paymentTokenForQuote->execute($cart);

        // iDeal express allows you to pick the shipping method, so we only need the subtotal.
        // Apple/Google Pay is only shown in the checkout, after the shipping method selection.
        // So they need shipping, coupons, etc.
        $total = (float)($isExpressCheckout ? $this->totals->getSubtotalInclTax() : $this->totals->getGrandTotal());

        $storeName = $this->scopeConfig->getValue('general/store_information/name');

        /** @var Session $session */
        $session = $mollie->send(new CreateSessionRequest(
            $this->transaction->getExpressRedirectUrl($cart, $paymentToken),
            $this->urlBuilder->getUrl('checkout/cart'),
            new Money($cart->getQuoteCurrencyCode(), number_format($total, 2, '.', '')),
            is_string($storeName) ? $storeName : __('Unnamed webshop')->render(),
            $this->getLines($isExpressCheckout),
            ['webhookUrl' => $this->transaction->getExpressWebhookUrl($cart)],
            ['quoteId' => $cart->getEntityId(), 'store_id' => storeId($cart->getStoreId())],
            $this->getRequiredCustomerDetails(),
        ));

        $cart->getPayment()->setAdditionalInformation('mollie_session_id', $session->id);

        return $session->clientAccessToken; // @phpstan-ignore property.notFound
    }

    /**
     * The express webhook creates the order from the cart, so it needs an email and addresses. A guest cart on the
     * cart page has neither, so let the Express Component collect what is missing. Mollie returns the data in
     * billingAddress and shippingAddress.
     *
     * @return string[]
     */
    private function getRequiredCustomerDetails(): array
    {
        return array_values(array_filter([
            $this->hasCustomerEmail() ? null : self::CUSTOMER_DETAIL_EMAIL,
            $this->isComplete($this->cart->getBillingAddress()) ? null : self::CUSTOMER_DETAIL_BILLING_ADDRESS,
            $this->needsShippingAddress() ? self::CUSTOMER_DETAIL_SHIPPING_ADDRESS : null,
        ]));
    }

    private function hasCustomerEmail(): bool
    {
        return (string)$this->cart->getCustomerEmail() !== ''
            || (string)$this->cart->getPayment()->getAdditionalInformation(self::GUEST_EMAIL_KEY) !== '';
    }

    private function needsShippingAddress(): bool
    {
        return !$this->cart->getIsVirtual() && !$this->isComplete($this->cart->getShippingAddress());
    }

    private function isComplete(?AddressInterface $address): bool
    {
        return $address !== null
            && (string)$address->getFirstname() !== ''
            && array_filter((array)$address->getStreet()) !== []
            && (string)$address->getPostcode() !== ''
            && (string)$address->getCountryId() !== '';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function getLines(bool $isExpressCheckout): array
    {
        $lines = [];
        $currency = $this->cart->getQuoteCurrencyCode();
        foreach ($this->cart->getItems() ?? [] as $item) {
            $lines[] = [
                'type' => $item->getIsVirtual() ? 'digital' : 'physical',
                'description' => '[' . $item->getSku() . '] ' . $item->getName(),
                'quantity' => (int)$item->getQty(),
                'unitPrice' => $this->mollieHelper->getAmountArray($currency, (float)$item->getPriceInclTax()),
                'totalAmount' => $this->mollieHelper->getAmountArray($currency, (float)$item->getPriceInclTax() * (int)$item->getQty()),
                'totalWeight' => [
                    'value' => $this->getWeightInGrams($item),
                    'unit' => 'g',
                ],
            ];
        }

        if ($isExpressCheckout) {
            return $lines;
        }

        $exclude = ['subtotal', 'tax', 'grand_total', 'subtotal_incl_tax'];
        foreach ($this->totals->getTotalSegments() as $segment) {
            if (in_array($segment->getCode(), $exclude)) {
                continue;
            }

            $value = $segment->getCode() === 'shipping'
                ? (float)$this->cart->getShippingAddress()->getShippingInclTax()
                : (float)$segment->getValue();

            if ($value == 0.0) {
                continue;
            }

            $lines[] = [
                'type' => $this->getSegmentType($segment->getCode(), $value),
                'description' => $segment->getTitle(),
                'quantity' => 1,
                'unitPrice' => $this->mollieHelper->getAmountArray($currency, $value),
                'totalAmount' => $this->mollieHelper->getAmountArray($currency, $value),
            ];
        }

        return $lines;
    }

    private function getSegmentType(string $code, float $value): string
    {
        if ($code === 'shipping') {
            return 'shipping_fee';
        }

        return $value < 0 ? 'discount' : 'surcharge';
    }

    private function getWeightInGrams(CartItemInterface $item): string
    {
        $weight = (float)$item->getWeight();
        if ($this->scopeConfig->getValue('general/locale/weight_unit') == 'lbs') {
            $weight *= 0.45359237;
        }

        // Convert kgs to grams
        return number_format($weight * 1000, 0, '.', '');
    }
}
