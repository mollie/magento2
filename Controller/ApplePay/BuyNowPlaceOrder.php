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
use Magento\Quote\Api\GuestCartRepositoryInterface;
use Magento\Quote\Model\QuoteManagement;

class BuyNowPlaceOrder extends Action
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

    public function __construct(
        Context $context,
        GuestCartRepositoryInterface $guestCartRepository,
        CartRepositoryInterface $cartRepository,
        QuoteManagement $quoteManagement,
        Session $checkoutSession
    ) {
        parent::__construct($context);

        $this->guestCartRepository = $guestCartRepository;
        $this->cartRepository = $cartRepository;
        $this->quoteManagement = $quoteManagement;
        $this->checkoutSession = $checkoutSession;
    }

    public function execute()
    {
        $cart = $this->guestCartRepository->get($this->getRequest()->getParam('cartId'));

        $shippingAddress = $cart->getShippingAddress();
        $this->updateAddress($shippingAddress, $this->getRequest()->getParam('shippingAddress'));
        $this->updateAddress($cart->getBillingAddress(), $this->getRequest()->getParam('billingAddress'));

        $cart->setCustomerEmail($this->getRequest()->getParam('shippingAddress')['emailAddress']);

        $shippingAddress->setCollectShippingRates(true);
        $shippingAddress->collectShippingRates();
        $shippingAddress->setShippingMethod($this->getRequest()->getParam('shippingMethod')['identifier']);

        $cart->setPaymentMethod('mollie_methods_applepay');
        $cart->setCustomerIsGuest(true);

        $cart->collectTotals();
        $this->cartRepository->save($cart);
        $cart->getPayment()->addData(['method' => 'mollie_methods_applepay']);

        $order = $this->quoteManagement->submit($cart);

        $this->checkoutSession->clearHelperData();
        $this->checkoutSession
            ->setLastQuoteId($cart->getId())
            ->setLastSuccessQuoteId($cart->getId())
            ->setLastOrderId($order->getId());

        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        return $response->setData(['order_placed' => false, 'order' => $order]);
    }

    private function updateAddress(AddressInterface $address, array $input)
    {
        $address->addData([
            AddressInterface::KEY_STREET => implode(PHP_EOL, $input['addressLines']),
            AddressInterface::KEY_COUNTRY_ID => $input['countryCode'],
            AddressInterface::KEY_LASTNAME => $input['familyName'],
            AddressInterface::KEY_FIRSTNAME => $input['givenName'],
            AddressInterface::KEY_CITY => $input['locality'],
            AddressInterface::KEY_POSTCODE => $input['postalCode'],
        ]);

        if (isset($input['phoneNumber'])) {
            $address->setTelephone($input['phoneNumber']);
        }

        if ($address->getAddressType() == \Magento\Quote\Model\Quote\Address::ADDRESS_TYPE_BILLING) {
            $input = $this->getRequest()->getParam('shippingAddress');
            $address->setTelephone($input['phoneNumber']);
        }
    }
}
