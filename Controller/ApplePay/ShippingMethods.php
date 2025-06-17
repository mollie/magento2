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
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\GuestCartRepositoryInterface;
use Magento\Quote\Api\ShippingMethodManagementInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\Total as AddressTotal;
use Mollie\Payment\Service\Magento\ChangeShippingMethodForQuote;

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
     * @var ShippingMethodManagementInterface
     */
    private $shippingMethodManagement;
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    /**
     * @var ChangeShippingMethodForQuote
     */
    private $changeShippingMethodForQuote;

    public function __construct(
        Context $context,
        CartRepositoryInterface $cartRepository,
        ShippingMethodManagementInterface $shippingMethodManagement,
        CheckoutSession $checkoutSession,
        GuestCartRepositoryInterface $guestCartRepository,
        ChangeShippingMethodForQuote $changeShippingMethodForQuote
    ) {
        parent::__construct($context);
        $this->shippingMethodManagement = $shippingMethodManagement;
        $this->guestCartRepository = $guestCartRepository;
        $this->cartRepository = $cartRepository;
        $this->checkoutSession = $checkoutSession;
        $this->changeShippingMethodForQuote = $changeShippingMethodForQuote;
    }

    public function execute()
    {
        $cart = $this->getCart();

        /**
         * @var Address $address
         */
        $address = $cart->getShippingAddress();
        $address->setData(null);
        $address->setCountryId(strtoupper($this->getRequest()->getParam('countryCode')));
        $address->setPostcode($this->getRequest()->getParam('postalCode'));

        if ($this->getRequest()->getParam('shippingMethod')) {
            $this->changeShippingMethodForQuote->execute(
                $address,
                $this->getRequest()->getParam('shippingMethod')['identifier']
            );
        }

        $cart->setPaymentMethod('mollie_methods_applepay');
        $cart->getPayment()->importData(['method' => 'mollie_methods_applepay']);
        $this->cartRepository->save($cart);
        $cart->collectTotals();

        $methods = $this->shippingMethodManagement->getList($cart->getId());
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        return $response->setData([
            'shipping_methods' => array_map(function ($method) {
                return [
                    // Magento uses an _ (underscore) to separate the carrier and method, but those can have an
                    // underscore as well. So separate by a different divider to prevent errors.
                    'identifier' => $method->getCarrierCode() . '__SPLIT__' . $method->getMethodCode(),
                    'label' => $method->getMethodTitle() . ' - ' . $method->getCarrierTitle(),
                    'amount' => number_format($method->getPriceInclTax() ?: 0.0, 2, '.', ''),
                    'detail' => '',
                ];
            }, $methods),
            'totals' => array_map(function (AddressTotal $total) {
                return [
                    'type' => 'final',
                    'code' => $total->getCode(),
                    'label' => $total->getData('title'),
                    'amount' => number_format($total->getData('value') ?: 0.0, 2, '.', ''),
                ];
            }, array_values($cart->getTotals()))
        ]);
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
