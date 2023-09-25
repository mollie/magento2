<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\ApplePay;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\AddressInterfaceFactory;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\PaymentInterfaceFactory;
use Magento\Quote\Api\GuestCartRepositoryInterface;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\Quote\Api\ShippingMethodManagementInterface;
use Magento\Quote\Model\Quote\Address\Total as AddressTotal;

class ShippingMethods extends Action
{
    /**
     * @var CartRepositoryInterface
     */
    private $cartRepository;

    /**
     * @var GuestCartRepositoryInterface
     */
    private $guestCartRepository;

    /**
     * @var AddressInterfaceFactory
     */
    private $addressFactory;

    /**
     * @var PaymentMethodManagementInterface
     */
    private $paymentMethodManagement;

    /**
     * @var PaymentInterfaceFactory
     */
    private $paymentInterfaceFactory;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var ShippingMethodManagementInterface
     */
    private $shippingMethodManagement;

    public function __construct(
        Context $context,
        CartRepositoryInterface $cartRepository,
        GuestCartRepositoryInterface $guestCartRepository,
        ShippingMethodManagementInterface $shippingMethodManagement,
        AddressInterfaceFactory $addressFactory,
        PaymentMethodManagementInterface $paymentMethodManagement,
        PaymentInterfaceFactory $paymentInterfaceFactory,
        CheckoutSession $checkoutSession
    ) {
        parent::__construct($context);

        $this->guestCartRepository = $guestCartRepository;
        $this->shippingMethodManagement = $shippingMethodManagement;
        $this->addressFactory = $addressFactory;
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->paymentInterfaceFactory = $paymentInterfaceFactory;
        $this->cartRepository = $cartRepository;
        $this->checkoutSession = $checkoutSession;
    }

    public function execute()
    {
        $cart = $this->getCart();

        $address = $this->addressFactory->create();
        $address->setCountryId($this->getRequest()->getParam('countryCode'));
        $address->setPostcode($this->getRequest()->getParam('postalCode'));

        $cart->setShippingAddress($address);

        $cart->collectTotals();
        $this->cartRepository->save($cart);

        if ($this->getRequest()->getParam('shippingMethod')) {
            $this->addShippingMethod($cart, $this->getRequest()->getParam('shippingMethod')['identifier']);
        }

        $methods = $this->shippingMethodManagement->getList($cart->getId());
        $this->setDefaultShippingMethod($cart, $methods);

        /** @var PaymentInterface $payment */
        $payment = $this->paymentInterfaceFactory->create();
        $payment->setMethod('mollie_methods_applepay');
        $this->paymentMethodManagement->set($cart->getId(), $payment);
        $cart = $this->cartRepository->get($cart->getId());

        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        return $response->setData([
            'shipping_methods' => array_map(function ($method) {
                return [
                    'identifier' => $method->getCarrierCode() . '_' . $method->getMethodCode(),
                    'label' => $method->getMethodTitle() . ' - ' . $method->getCarrierTitle(),
                    'amount' => number_format($method->getPriceInclTax(), 2, '.', ''),
                    'detail' => '',
                ];
            }, $methods),
            'totals' => array_map(function (AddressTotal $total) {
                return [
                    'type' => 'final',
                    'label' => $total->getData('title'),
                    'amount' => number_format($total->getData('value'), 2, '.', ''),
                ];
            }, array_values($cart->getTotals()))
        ]);
    }

    /**
     * @param CartInterface $cart
     * @param \Magento\Quote\Api\Data\ShippingMethodInterface[] $methods
     */
    private function setDefaultShippingMethod(CartInterface $cart, array $methods)
    {
        if ($cart->getShippingAddress()->getShippingMethod()) {
            return;
        }

        $method = array_shift($methods);
        if (!$method) {
            return;
        }

        $this->addShippingMethod($cart, $method->getCarrierCode() . '_' . $method->getMethodCode());
        $this->cartRepository->save($cart);
    }

    private function addShippingMethod(CartInterface $cart, string $identifier)
    {
        $address = $cart->getShippingAddress();

        $address->setShippingMethod($identifier);
        $address->setCollectShippingRates(true);
        $address->save();

        $address->collectShippingRates();
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
