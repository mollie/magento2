<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Controller\ApplePay;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\GuestCartRepositoryInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Service\PaymentToken\Generate;
use Mollie\Payment\Service\Quote\SetRegionFromApplePayAddress;

class PlaceOrder extends Action implements HttpPostActionInterface
{
    public function __construct(
        Context $context,
        private readonly GuestCartRepositoryInterface $guestCartRepository,
        private readonly CartRepositoryInterface $cartRepository,
        private readonly QuoteManagement $quoteManagement,
        private readonly Session $checkoutSession,
        private readonly Generate $paymentToken,
        private readonly SetRegionFromApplePayAddress $setRegionFromApplePayAddress,
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly Config $config,
        private readonly ScopeConfigInterface $scopeConfig,
    ) {
        parent::__construct($context);
    }

    public function execute(): Json
    {
        $cart = $this->getCart();

        $shippingAddress = $cart->getShippingAddress();
        $this->updateAddress($shippingAddress, $this->getRequest()->getParam('shippingAddress'));
        $this->updateAddress($cart->getBillingAddress(), $this->getRequest()->getParam('billingAddress'));

        $cart->setCustomerEmail($this->getRequest()->getParam('shippingAddress')['emailAddress']);

        // Orders with digital products can't have a shipping method
        if ($this->getRequest()->getParam('shippingMethod')) {
            $shippingAddress->setShippingMethod(
                str_replace(
                    '__SPLIT__',
                    '_',
                    $this->getRequest()->getParam('shippingMethod')['identifier'],
                ),
            );
        }

        $cart->setPaymentMethod('mollie_methods_applepay');
        $cart->setCustomerIsGuest(true);

        $cart->collectTotals();
        $this->cartRepository->save($cart);
        $cart->getPayment()->addData(['method' => 'mollie_methods_applepay']);

        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        try {
            /** @var OrderInterface $order */
            $order = $this->quoteManagement->submit($cart);
        } catch (Exception $exception) {
            $this->config->addToLog('error', [
                'message' => 'Error while try place Apple Pay order',
                'quote_id' => $cart->getId(),
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            $response->setHttpResponseCode(400);
            return $response->setData(['error' => true, 'message' => $exception->getMessage()]);
        }

        $order->getPayment()->setAdditionalInformation(
            'applepay_payment_token',
            $this->getRequest()->getParam('applePayPaymentToken'),
        );

        $this->orderRepository->save($order);

        $paymentToken = $this->paymentToken->forOrder($order);

        $url = $this->_url->getUrl('mollie/checkout/redirect', ['paymentToken' => $paymentToken->getToken()]);

        $cart->setIsActive(false);

        $this->checkoutSession->clearHelperData();
        $this->checkoutSession
            ->setLastQuoteId($cart->getId())
            ->setLastSuccessQuoteId($cart->getId())
            ->setLastRealOrderId($order->getIncrementId())
            ->setLastOrderId($order->getId());

        return $response->setData(['url' => $url, 'error' => false, 'message' => '']);
    }

    /**
     * @return CartInterface
     * @throws LocalizedException
     * @throws NoSuchEntityException*/
    private function getCart(): CartInterface
    {
        if ($cartId = $this->getRequest()->getParam('cartId')) {
            return $this->guestCartRepository->get($cartId);
        }

        return $this->checkoutSession->getQuote();
    }

    private function getAddressLines($addressLines): string
    {
        $maxLinesCount = $this->scopeConfig->getValue('customer/address/street_lines');
        $linesCount = count($addressLines);

        if ($linesCount > $maxLinesCount) {
            return implode(', ', $addressLines);
        }

        return implode(PHP_EOL, $addressLines);
    }

    private function updateAddress(AddressInterface $address, array $input): void
    {
        $address->addData([
            AddressInterface::KEY_STREET => $this->getAddressLines($input['addressLines']),
            AddressInterface::KEY_COUNTRY_ID => strtoupper($input['countryCode']),
            // Sometimes the familyName may be empty, fall back to -- in that case.
            AddressInterface::KEY_LASTNAME => $input['familyName'] ?: '--',
            AddressInterface::KEY_FIRSTNAME => $input['givenName'],
            AddressInterface::KEY_CITY => $input['locality'],
            AddressInterface::KEY_POSTCODE => $input['postalCode'],
        ]);

        $this->setRegionFromApplePayAddress->execute($address, $input);

        if (isset($input['phoneNumber'])) {
            $address->setTelephone($input['phoneNumber']);
        }

        if ($address->getAddressType() == Address::ADDRESS_TYPE_BILLING) {
            $input = $this->getRequest()->getParam('shippingAddress');
            $address->setTelephone($input['phoneNumber']);
        }
    }
}
