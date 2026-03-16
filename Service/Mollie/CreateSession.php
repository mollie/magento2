<?php

namespace Mollie\Payment\Service\Mollie;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\UrlInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Mollie\Api\Http\Data\Money;
use Mollie\Api\Resources\Session;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Service\Mollie\Api\CreateSessionRequest;
use Mollie\Payment\Service\Order\Transaction;
use Mollie\Payment\Service\PaymentToken\PaymentTokenForQuote;

class CreateSession
{
    public function __construct(
        private readonly MollieApiClient $mollieApiClient,
        private readonly PaymentTokenForQuote $paymentTokenForQuote,
        private readonly Transaction $transaction,
        private readonly UrlInterface $urlBuilder,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly General $mollieHelper,
    ) {
    }

    public function execute(CartInterface $cart): string
    {
        $mollie = $this->mollieApiClient->loadByStore($cart->getStoreId());
        $paymentToken = $this->paymentTokenForQuote->execute($cart);

        /** @var Session $session */
        $session = $mollie->send(new CreateSessionRequest(
            $this->transaction->getExpressRedirectUrl($cart, $paymentToken),
            $this->urlBuilder->getUrl('checkout/cart'),
            new Money($cart->getQuoteCurrencyCode(), number_format((float)$cart->getSubtotal(), 2)),
            $this->scopeConfig->getValue('general/store_information/name') ?? __('Unnamed webshop')->render(),
            $this->getLines($cart),
            ['webhookUrl' => $this->transaction->getExpressWebhookUrl($cart)],
            ['quoteId' => $cart->getEntityId(), 'store_id' => storeId($cart->getStoreId())],
        ));

        $cart->getPayment()->setAdditionalInformation('mollie_session_id', $session->id);

        return $session->clientAccessToken; // @phpstan-ignore property.notFound
    }

    private function getLines(CartInterface $cart): array
    {
        $lines = [];
        $currency = $cart->getQuoteCurrencyCode();
        foreach ($cart->getItems() as $item) {
            $lines[] = [
                'description' => '[' . $item->getSku() . '] ' . $item->getName(),
                'quantity' => (int)$item->getQty(),
                'unitPrice' => $this->mollieHelper->getAmountArray($currency, (float)$item->getPrice()),
                'totalAmount' => $this->mollieHelper->getAmountArray($currency, (float)$item->getPrice() * (int)$item->getQty()),
                'totalWeight' => [
                    'value' => $this->getWeightInGrams($item),
                    'unit' => 'g',
                ],
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
