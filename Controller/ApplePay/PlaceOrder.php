<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\ApplePay;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\GuestCartRepositoryInterface;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Api\Webapi\PaymentTokenRequestInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Service\PaymentToken\Generate;
use Mollie\Payment\Service\Quote\SetRegionFromApplePayAddress;

class PlaceOrder extends Action
{
    /**
     * @var GuestCartRepositoryInterface
     */
    private $guestCartRepository;

    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var QuoteManagement
     */
    private $quoteManagement;

    /**
     * @var Session
     */
    private $checkoutSession;

    /**
     * @var PaymentTokenRequestInterface
     */
    private $paymentTokenRequest;

    /**
     * @var Generate
     */
    private $paymentToken;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var SetRegionFromApplePayAddress
     */
    private $setRegionFromApplePayAddress;
    /**
     * @var Config
     */
    private $config;

    public function __construct(
        Context $context,
        GuestCartRepositoryInterface $guestCartRepository,
        CartRepositoryInterface $cartRepository,
        QuoteManagement $quoteManagement,
        Session $checkoutSession,
        Generate $paymentToken,
        SetRegionFromApplePayAddress $setRegionFromApplePayAddress,
        OrderRepositoryInterface $orderRepository,
        Config $config
    ) {
        parent::__construct($context);

        $this->guestCartRepository = $guestCartRepository;
        $this->cartRepository = $cartRepository;
        $this->quoteManagement = $quoteManagement;
        $this->checkoutSession = $checkoutSession;
        $this->paymentToken = $paymentToken;
        $this->orderRepository = $orderRepository;
        $this->setRegionFromApplePayAddress = $setRegionFromApplePayAddress;
        $this->config = $config;
    }

    public function execute()
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
                    $this->getRequest()->getParam('shippingMethod')['identifier']
                )
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
        } catch (\Exception $exception) {
            $this->config->addToLog('error', [
                'message' => 'Error while try place Apple Pay order',
                'quote_id' => $cart->getId(),
                'exception' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return $response->setData(['error' => true, 'error_message' => $exception->getMessage()]);
        }

        $order->getPayment()->setAdditionalInformation(
            'applepay_payment_token',
            $this->getRequest()->getParam('applePayPaymentToken')
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

        return $response->setData(['url' => $url, 'error' => false, 'error_message' => '']);
    }

    private function updateAddress(AddressInterface $address, array $input)
    {
        $address->addData([
            AddressInterface::KEY_STREET => implode(PHP_EOL, $input['addressLines']),
            AddressInterface::KEY_COUNTRY_ID => strtoupper($input['countryCode']),
            AddressInterface::KEY_LASTNAME => $input['familyName'],
            AddressInterface::KEY_FIRSTNAME => $input['givenName'],
            AddressInterface::KEY_CITY => $input['locality'],
            AddressInterface::KEY_POSTCODE => $input['postalCode'],
        ]);

        $this->setRegionFromApplePayAddress->execute($address, $input);

        if (isset($input['phoneNumber'])) {
            $address->setTelephone($input['phoneNumber']);
        }

        if ($address->getAddressType() == \Magento\Quote\Model\Quote\Address::ADDRESS_TYPE_BILLING) {
            $input = $this->getRequest()->getParam('shippingAddress');
            $address->setTelephone($input['phoneNumber']);
        }
    }

    /**
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @return CartInterface
     */
    public function getCart(): CartInterface
    {
        if ($cartId = $this->getRequest()->getParam('cartId')) {
            return $this->guestCartRepository->get($cartId);
        }

        return $this->checkoutSession->getQuote();
    }
}
