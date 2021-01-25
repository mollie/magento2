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
use Magento\Quote\Api\Data\AddressInterfaceFactory;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Magento\Quote\Api\Data\PaymentInterfaceFactory;
use Magento\Quote\Api\GuestCartRepositoryInterface;
use Magento\Quote\Api\GuestShippingMethodManagementInterface;
use Magento\Quote\Api\PaymentMethodManagementInterface;
use Magento\Quote\Model\Quote\TotalsCollector;
use Mollie\Payment\Model\Methods\ApplePay;

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
     * @var GuestShippingMethodManagementInterface
     */
    private $guestShippingMethodManagement;

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
     * @var TotalsCollector
     */
    private $totalsCollector;

    public function __construct(
        Context $context,
        CartRepositoryInterface $cartRepository,
        GuestCartRepositoryInterface $guestCartRepository,
        GuestShippingMethodManagementInterface $guestShippingMethodManagement,
        AddressInterfaceFactory $addressFactory,
        PaymentMethodManagementInterface $paymentMethodManagement,
        PaymentInterfaceFactory $paymentInterfaceFactory,
        TotalsCollector $totalsCollector
    ) {
        parent::__construct($context);
        $this->guestCartRepository = $guestCartRepository;
        $this->guestShippingMethodManagement = $guestShippingMethodManagement;
        $this->addressFactory = $addressFactory;
        $this->paymentMethodManagement = $paymentMethodManagement;
        $this->paymentInterfaceFactory = $paymentInterfaceFactory;
        $this->totalsCollector = $totalsCollector;
        $this->cartRepository = $cartRepository;
    }

    public function execute()
    {
        $cartId = $this->getRequest()->getParam('cartId');
        $cart = $this->guestCartRepository->get($cartId);

        $address = $this->addressFactory->create();
        $address->setCountryId($this->getRequest()->getParam('countryCode'));
        $address->setPostcode($this->getRequest()->getParam('postalCode'));
        $cart->setShippingAddress($address);
        $this->cartRepository->save($cart);

        $methods = $this->guestShippingMethodManagement->getList($cartId);

        /** @var PaymentInterface $payment */
        $payment = $this->paymentInterfaceFactory->create();
        $payment->setMethod('mollie_methods_applepay');
        $this->paymentMethodManagement->set($cart->getId(), $payment);
        $cart = $this->cartRepository->get($cart->getId());

        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        return $response->setData([
            'mollie_payment_fee' => $this->getPaymentFee($cart),
            'shipping_methods' => array_map(function ($method) {
                return [
                    'identifier' => $method->getCarrierCode() . '_' . $method->getMethodCode(),
                    'label' => $method->getCarrierTitle(),
                    'amount' => $method->getPriceInclTax(),
                    'detail' => '',
                ];
            }, $methods)
        ]);
    }

    private function getPaymentFee(CartInterface $cart)
    {
        $extensionAttributes = $cart->getExtensionAttributes();
        if (!$extensionAttributes) {
            return 0;
        }

        return $extensionAttributes->getMolliePaymentFee() + $extensionAttributes->getBaseMolliePaymentFeeTax();
    }
}
