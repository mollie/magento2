<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Controller\Express;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\CartItemInterface;
use Mollie\Api\Http\Data\Money;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Service\Mollie\Api\CreateSessionRequest;
use Mollie\Payment\Service\Mollie\MollieApiClient;
use Mollie\Payment\Service\Order\Transaction;
use Mollie\Payment\Service\PaymentToken\PaymentTokenForQuote;

class CreateSession implements HttpPostActionInterface
{
    public function __construct(
        readonly private Session $checkoutSession,
        readonly private MollieApiClient $mollieApiClient,
        readonly private General $mollieHelper,
        readonly private JsonFactory $jsonFactory,
        readonly private CartRepositoryInterface $cartRepository,
        readonly private Transaction $transaction,
        readonly private PaymentTokenForQuote $paymentTokenForQuote,
        readonly private ScopeConfigInterface $scopeConfig,
        readonly private RequestInterface $request,
    ) {}

    public function execute()
    {
        $cart = $this->checkoutSession->getQuote();
        $this->setEmailOnCart($cart);
        $cart->getPayment()->setMethod('mollie_methods_expresscomponents');

        $mollie = $this->mollieApiClient->loadByStore($cart->getStoreId());
        $paymentToken = $this->paymentTokenForQuote->execute($cart);

        /** @var \Mollie\Api\Resources\Session $session */
        $session = $mollie->send(new CreateSessionRequest(
            $this->transaction->getExpressRedirectUrl($cart, $paymentToken),
            'https://www.example.com/',
            new Money($cart->getQuoteCurrencyCode(), number_format((float)$cart->getSubtotal(), 2)),
            $this->scopeConfig->getValue('general/store_information/name') ?? __('Unnamed webshop')->render(),
            $this->getLines($cart),
            ['webhookUrl' => $this->transaction->getExpressWebhookUrl($cart)],
            ['quoteId' => $cart->getEntityId(), 'store_id' => storeId($cart->getStoreId())],
        ));

        $cart->getPayment()->setAdditionalInformation('mollie_session_id', $session->id);
        $cart->collectTotals();
        $this->cartRepository->save($cart);

        return $this->jsonFactory->create()->setData([
            'clientAccessToken' => $session->clientAccessToken, // @phpstan-ignore property.notFound
        ]);
    }

    public function getWeightInGrams(CartItemInterface $item): string
    {
        $weight = (float)$item->getWeight();
        if ($this->scopeConfig->getValue('general/locale/weight_unit') == 'lbs') {
            $weight *= 0.45359237;
        }

        // Convert kgs to grams
        return number_format($weight * 1000, 0, '.', '');
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

    private function setEmailOnCart(CartInterface|\Magento\Quote\Model\Quote $cart): void {
        $email = $this->request->getParam('email');

        if (!$email) {
            return;
        }

        $cart->getPayment()->setAdditionalInformation('mollie_guest_email', $email);
    }
}
