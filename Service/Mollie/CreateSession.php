<?php

namespace Mollie\Payment\Service\Mollie;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\CartTotalRepositoryInterface;
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

        /** @var Session $session */
        $session = $mollie->send(new CreateSessionRequest(
            $this->transaction->getExpressRedirectUrl($cart, $paymentToken),
            $this->urlBuilder->getUrl('checkout/cart'),
            new Money($cart->getQuoteCurrencyCode(), number_format($total, 2)),
            $this->scopeConfig->getValue('general/store_information/name') ?? __('Unnamed webshop')->render(),
            $this->getLines($isExpressCheckout),
            ['webhookUrl' => $this->transaction->getExpressWebhookUrl($cart)],
            ['quoteId' => $cart->getEntityId(), 'store_id' => storeId($cart->getStoreId())],
        ));

        $cart->getPayment()->setAdditionalInformation('mollie_session_id', $session->id);

        return $session->clientAccessToken; // @phpstan-ignore property.notFound
    }

    private function getLines(bool $isExpressCheckout): array
    {
        $lines = [];
        $currency = $this->cart->getQuoteCurrencyCode();
        foreach ($this->cart->getItems() as $item) {
            $lines[] = [
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
                'description' => $segment->getTitle(),
                'quantity' => 1,
                'unitPrice' => $this->mollieHelper->getAmountArray($currency, $value),
                'totalAmount' => $this->mollieHelper->getAmountArray($currency, $value),
            ];
        }

        return $lines;
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
