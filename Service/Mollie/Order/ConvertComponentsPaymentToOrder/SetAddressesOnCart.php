<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Mollie\Order\ConvertComponentsPaymentToOrder;

use Magento\Quote\Api\Data\AddressInterfaceFactory;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Address;
use Mollie\Api\Resources\Payment;
use stdClass;

class SetAddressesOnCart
{
    public function __construct(
        private readonly AddressInterfaceFactory $addressFactory,
    ) {}

    public function execute(CartInterface $cart, Payment $payment): void
    {
        if ($payment->shippingAddress !== null) {
            $this->setAddressOnCart(Address::ADDRESS_TYPE_SHIPPING, $cart, $payment->shippingAddress);
            $cart->setCustomerEmail($payment->shippingAddress->email);
        }

        if ($payment->billingAddress !== null) {
            $this->setAddressOnCart(Address::ADDRESS_TYPE_BILLING, $cart, $payment->billingAddress);
            $cart->setCustomerEmail($payment->billingAddress->email);
        }
    }

    private function setAddressOnCart(string $type, CartInterface $cart, stdClass $address): void
    {
        $cartAddress = $this->addressFactory->create();
        $cartAddress->setFirstname($address->givenName);
        $cartAddress->setLastname($address->familyName);
        $cartAddress->setStreet([
            $address->streetAndNumber,
            $address->streetAdditional ?? null,
        ]);
        $cartAddress->setPostcode($address->postalCode);
        $cartAddress->setTelephone($address->phone);
        $cartAddress->setCity($address->city);

        if (property_exists($address, 'region')) {
            $cartAddress->setRegion($address->region);
        }

        $cartAddress->setCountryId($address->country);

        if ($type == Address::ADDRESS_TYPE_BILLING) {
            $cart->setBillingAddress($cartAddress);
        }

        if ($type == Address::ADDRESS_TYPE_SHIPPING) {
            $cart->setShippingAddress($cartAddress);
        }
    }
}
