<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Controller\ApplePay;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\StateException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\Data\ShippingMethodInterface;
use Magento\Quote\Api\GuestCartRepositoryInterface;
use Magento\Quote\Api\ShippingMethodManagementInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Quote\Model\Quote\Address\Total as AddressTotal;
use Mollie\Payment\Logger\MollieLogger;
use Mollie\Payment\Service\Magento\ChangeShippingMethodForQuote;
use Mollie\Payment\Service\Quote\IsValidApplePayShippingContact;

class ShippingMethods extends Action implements HttpPostActionInterface
{
    private const INVALID_SHIPPING_CONTACT = 'invalid_shipping_contact';
    private const SUPPORTS_INVALID_SHIPPING_CONTACT = 'supportsInvalidShippingContact';
    private const LEGACY_INVALID_SHIPPING_CONTACT_STATUS = 400;

    public function __construct(
        Context $context,
        private CartRepositoryInterface $cartRepository,
        private ShippingMethodManagementInterface $shippingMethodManagement,
        private CheckoutSession $checkoutSession,
        private GuestCartRepositoryInterface $guestCartRepository,
        private ChangeShippingMethodForQuote $changeShippingMethodForQuote,
        private IsValidApplePayShippingContact $isValidApplePayShippingContact,
        private MollieLogger $mollieLogger,
    )
    {
        parent::__construct($context);
    }

    public function execute(): Json
    {
        $cart = $this->getCart();
        $countryCode = strtoupper((string)$this->getRequest()->getParam('countryCode'));
        $postalCode = trim((string)$this->getRequest()->getParam('postalCode'));

        if ($this->supportsInvalidShippingContact() &&
            !$this->isValidApplePayShippingContact->check($countryCode, $postalCode, (int)$cart->getStoreId())
        ) {
            return $this->createInvalidShippingContactResponse();
        }

        try {
            $methods = $this->getShippingMethods($cart, $countryCode, $postalCode);
        } catch (InputException | StateException $exception) {
            $this->mollieLogger->addErrorLog('ApplePay shipping methods', [
                'message' => 'Unable to retrieve the Apple Pay shipping methods for the shipping contact',
                'exception' => $exception->getMessage(),
            ]);

            return $this->rejectShippingContact();
        }

        /** @var Json $response */
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        $transportObject = new DataObject(
            [
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
            ]
        );

        $this->_eventManager->dispatch(
            'mollie_applepay_direct_shippingmethods',
            ['transport' => $transportObject]
        );

        if ($this->supportsInvalidShippingContact() && $this->isUndeliverable($cart, $transportObject)) {
            return $this->createInvalidShippingContactResponse();
        }

        return $response->setData([
            'shipping_methods' => $transportObject->getData('shipping_methods'),
            'totals' => $transportObject->getData('totals'),
        ]);
    }

    /**
     * @return ShippingMethodInterface[]
     * @throws InputException
     * @throws StateException
     */
    private function getShippingMethods(CartInterface $cart, string $countryCode, string $postalCode): array
    {
        /**
         * @var Address $address
         */
        $address = $cart->getShippingAddress();
        $address->setCountryId($countryCode);
        $address->setPostcode($postalCode);

        if ($this->getRequest()->getParam('shippingMethod')) {
            $this->changeShippingMethodForQuote->execute(
                $address,
                $this->getRequest()->getParam('shippingMethod')['identifier'],
            );
        }

        $cart->setPaymentMethod('mollie_methods_applepay');
        $cart->getPayment()->importData(['method' => 'mollie_methods_applepay']);
        $this->cartRepository->save($cart);
        $cart->collectTotals();

        return $this->shippingMethodManagement->getList($cart->getId());
    }

    private function isUndeliverable(CartInterface $cart, DataObject $transportObject): bool
    {
        return $transportObject->getData('shipping_methods') === [] && !$cart->isVirtual();
    }

    private function rejectShippingContact(): Json
    {
        if ($this->supportsInvalidShippingContact()) {
            return $this->createInvalidShippingContactResponse();
        }

        return $this->createLegacyInvalidShippingContactResponse();
    }

    private function supportsInvalidShippingContact(): bool
    {
        return $this->getRequest()->getParam(self::SUPPORTS_INVALID_SHIPPING_CONTACT) === '1';
    }

    private function createLegacyInvalidShippingContactResponse(): Json
    {
        /** @var Json $response */
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $response->setHttpResponseCode(self::LEGACY_INVALID_SHIPPING_CONTACT_STATUS);

        return $response->setData([
            'error' => true,
            'message' => $this->getInvalidShippingContactMessage(),
        ]);
    }

    private function getInvalidShippingContactMessage(): string
    {
        return (string)__('Please check your shipping address and try again.');
    }

    private function createInvalidShippingContactResponse(): Json
    {
        /** @var Json $response */
        $response = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        return $response->setData([
            'shipping_methods' => [],
            'totals' => [],
            self::INVALID_SHIPPING_CONTACT => true,
            'message' => $this->getInvalidShippingContactMessage(),
        ]);
    }

    /**
     * @return CartInterface
     * @throws NoSuchEntityException
     */
    public function getCart(): CartInterface
    {
        if ($cartId = $this->getRequest()->getParam('cartId')) {
            return $this->guestCartRepository->get($cartId);
        }

        return $this->checkoutSession->getQuote();
    }
}
