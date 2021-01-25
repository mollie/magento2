<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\ApplePay;

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

    public function __construct(
        Context $context,
        GuestCartRepositoryInterface $guestCartRepository,
        CartRepositoryInterface $cartRepository,
        QuoteManagement $quoteManagement
    ) {
        parent::__construct($context);

        $this->guestCartRepository = $guestCartRepository;
        $this->cartRepository = $cartRepository;
        $this->quoteManagement = $quoteManagement;
    }

    public function execute()
    {
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $cart = $this->guestCartRepository->get($this->getRequest()->getParam('cartId'));

        $shippingAddress = $cart->getShippingAddress();
        $this->updateAddress($shippingAddress, $this->getRequest()->getParam('shippingAddress'));
        $this->updateAddress($cart->getBillingAddress(), $this->getRequest()->getParam('billingAddress'));

        $shippingAddress->setCollectShippingRates(true);
        $shippingAddress->collectShippingRates();
        $shippingAddress->setShippingMethod($this->getRequest()->getParam('shippingMethod'));

        $cart->setPaymentMethod('mollie_methods_applepay');
        $this->cartRepository->save($cart);

        $cart->collectTotals();
        $this->cartRepository->save($cart);

        $order = $this->quoteManagement->submit($cart);

        return $response->setData(['order_placed' => false, 'order' => $order]);
    }

    private function updateAddress(AddressInterface $address, array $shippingAddressInput)
    {
        $address->setData([
            AddressInterface::KEY_STREET => $shippingAddressInput['addressLines'],
            AddressInterface::KEY_COUNTRY_ID => $shippingAddressInput['countryCode'],
            AddressInterface::KEY_LASTNAME => $shippingAddressInput['familyName'],
            AddressInterface::KEY_FIRSTNAME => $shippingAddressInput['givenName'],
            AddressInterface::KEY_CITY => $shippingAddressInput['locality'],
            AddressInterface::KEY_POSTCODE => $shippingAddressInput['postalCode'],
        ]);
    }
}
