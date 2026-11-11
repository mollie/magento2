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
use Mollie\Payment\Exceptions\CustomerDetailsRequired;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Service\Mollie\Api\CreateSessionRequest;
use Mollie\Payment\Service\Mollie\Express\ExpressComponentsBeta;
use Mollie\Payment\Service\Mollie\Express\ExpressSession;
use Mollie\Payment\Service\Mollie\Express\ProductBuyRequest;
use Mollie\Payment\Service\Mollie\Express\ProductPaymentMetadata;
use Mollie\Payment\Service\Mollie\Express\SessionAddresses;
use Mollie\Payment\Service\Order\Transaction;
use Mollie\Payment\Service\PaymentToken\Generate;
use Mollie\Payment\Service\PaymentToken\PaymentTokenForQuote;

class CreateSession
{
    private const GUEST_EMAIL_KEY = 'mollie_guest_email';
    private const CUSTOMER_DETAIL_EMAIL = 'email';
    private const CUSTOMER_DETAIL_BILLING_ADDRESS = 'billing-address';
    private const CUSTOMER_DETAIL_SHIPPING_ADDRESS = 'shipping-address';

    private CartInterface $cart;
    private bool $isExpressCheckout;
    private string $shippingCallbackUrl;

    /** @var array<string, string> */
    private array $billingAddressToSend;

    /** @var array<string, string> */
    private array $shippingAddressToSend;

    public function __construct(
        private readonly MollieApiClient $mollieApiClient,
        private readonly PaymentTokenForQuote $paymentTokenForQuote,
        private readonly Generate $generatePaymentToken,
        private readonly Transaction $transaction,
        private readonly UrlInterface $urlBuilder,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly General $mollieHelper,
        private readonly CartTotalRepositoryInterface $cartTotalRepository,
        private readonly ExpressComponentsBeta $beta,
        private readonly SessionAddresses $sessionAddresses,
    ) {
    }

    public function execute(CartInterface $cart, bool $isExpressCheckout = true): string
    {
        $this->prepare($cart, $isExpressCheckout, $this->transaction->getExpressShippingOptionsUrl($cart));
        $paymentToken = $this->paymentTokenForQuote->execute($cart);

        $session = $this->send(
            $paymentToken,
            $this->transaction->getExpressWebhookUrl($cart),
            ['quoteId' => $cart->getEntityId(), 'store_id' => storeId($cart->getStoreId())],
        );

        return $session->clientAccessToken; // @phpstan-ignore property.notFound
    }

    /**
     * The product page quote is never saved. Its token gets a row once the webhook placed the order, and the
     * metadata holds what is needed to build the quote again.
     */
    public function executeForProduct(CartInterface $cart, ProductBuyRequest $buyRequest): ExpressSession
    {
        $storeId = (int)$cart->getStoreId();
        $this->prepare($cart, true, $this->transaction->getExpressProductShippingOptionsUrl($storeId));
        $paymentToken = $this->generatePaymentToken->unusedToken();
        $customerId = $cart->getCustomer()->getId();

        $metadata = new ProductPaymentMetadata(
            $buyRequest,
            $paymentToken,
            $storeId,
            $customerId === null ? null : (int)$customerId,
        );

        $session = $this->send(
            $paymentToken,
            $this->transaction->getExpressProductWebhookUrl($storeId),
            $metadata->toArray(),
        );

        return new ExpressSession(
            (string)$session->id,
            (string)$session->clientAccessToken, // @phpstan-ignore property.notFound
            $paymentToken,
        );
    }

    private function prepare(CartInterface $cart, bool $isExpressCheckout, string $shippingCallbackUrl): void
    {
        $this->cart = $cart;
        $this->isExpressCheckout = $isExpressCheckout;
        $this->shippingCallbackUrl = $this->canOfferShippingOptions() ? $shippingCallbackUrl : '';
        $this->billingAddressToSend = $this->sessionAddresses->billing($cart);
        $this->shippingAddressToSend = $this->canSendShippingAddress() ? $this->sessionAddresses->shipping($cart) : [];
    }

    /*
     * Mollie only accepts the shipping callback together with a shipping address it collects itself, so the callback
     * and a merchant supplied shipping address exclude each other.
     */
    private function canSendShippingAddress(): bool
    {
        return $this->shippingCallbackUrl === '' && !$this->cart->getIsVirtual();
    }

    private function canOfferShippingOptions(): bool
    {
        return $this->isExpressCheckout
            && !$this->cart->getIsVirtual()
            && $this->beta->isEnabled(storeId($this->cart->getStoreId()));
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function send(string $paymentToken, string $webhookUrl, array $metadata): Session
    {
        $requiredCustomerDetails = $this->getRequiredCustomerDetails();
        $this->guardCustomerDetailsAreSupported($requiredCustomerDetails);

        $lines = $this->getLines($this->isExpressCheckout);
        $storeName = $this->scopeConfig->getValue('general/store_information/name');

        /** @var Session $session */
        $session = $this->mollieApiClient->loadByStore($this->cart->getStoreId())->send(new CreateSessionRequest(
            $this->transaction->getExpressRedirectUrl($this->cart, $paymentToken),
            $this->urlBuilder->getUrl('checkout/cart'),
            new Money($this->cart->getQuoteCurrencyCode(), number_format($this->getTotal($lines), 2, '.', '')),
            is_string($storeName) ? $storeName : __('Unnamed webshop')->render(),
            $lines,
            ['webhookUrl' => $webhookUrl],
            $metadata,
            $requiredCustomerDetails,
            $this->getShipping(),
            $this->billingAddressToSend,
            $this->shippingAddressToSend,
        ));

        $this->cart->getPayment()->setAdditionalInformation('mollie_session_id', $session->id);

        return $session;
    }

    /**
     * Mollie answers requiredCustomerDetails with an error for accounts outside its beta. Sending it anyway would
     * log an error for every guest that opens the minicart.
     *
     * @param string[] $requiredCustomerDetails
     * @throws CustomerDetailsRequired
     */
    private function guardCustomerDetailsAreSupported(array $requiredCustomerDetails): void
    {
        if ($requiredCustomerDetails === [] || $this->beta->isEnabled(storeId($this->cart->getStoreId()))) {
            return;
        }

        throw new CustomerDetailsRequired(
            __('The express payment needs customer details that Mollie can only collect in the beta.')
        );
    }

    /**
     * On the express path Mollie adds the shipping costs, so the amount is what the lines add up to. Apple Pay and
     * Google Pay are only shown in the checkout, after the shipping method selection, so they need the grand total.
     *
     * @param array<int, array<string, mixed>> $lines
     */
    private function getTotal(array $lines): float
    {
        if (!$this->isExpressCheckout) {
            return (float)$this->getTotals()->getGrandTotal();
        }

        return array_reduce(
            $lines,
            static fn (float $total, array $line): float => $total + (float)$line['totalAmount']['value'],
            0.0,
        );
    }

    private function getTotals(): TotalsInterface
    {
        return $this->cartTotalRepository->get($this->cart->getId());
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
            $this->hasBillingAddress() ? null : self::CUSTOMER_DETAIL_BILLING_ADDRESS,
            $this->needsShippingAddress() ? self::CUSTOMER_DETAIL_SHIPPING_ADDRESS : null,
        ]));
    }

    private function hasBillingAddress(): bool
    {
        return $this->billingAddressToSend !== [] || $this->isComplete($this->cart->getBillingAddress());
    }

    /**
     * On the express path no shipping method is selected yet. Mollie calls this URL with the address the customer
     * picked in the Express Component and shows the returned options. Mollie only accepts this together with a
     * required shipping address and without a shipping_fee line.
     *
     * @return array<string, string>
     */
    private function getShipping(): array
    {
        return $this->shippingCallbackUrl === '' ? [] : ['callbackUrl' => $this->shippingCallbackUrl];
    }

    private function hasCustomerEmail(): bool
    {
        return (string)$this->cart->getCustomerEmail() !== ''
            || (string)$this->cart->getPayment()->getAdditionalInformation(self::GUEST_EMAIL_KEY) !== '';
    }

    private function needsShippingAddress(): bool
    {
        if ($this->shippingCallbackUrl !== '') {
            return true;
        }

        if ($this->cart->getIsVirtual()) {
            return false;
        }

        return $this->shippingAddressToSend === [] && !$this->isComplete($this->cart->getShippingAddress());
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
        foreach ($this->getTotals()->getTotalSegments() as $segment) {
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
