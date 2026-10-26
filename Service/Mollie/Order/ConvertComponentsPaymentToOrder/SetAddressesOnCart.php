<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Order\ConvertComponentsPaymentToOrder;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Api\Data\AddressInterface;
use Magento\Quote\Api\Data\AddressInterfaceFactory;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Address;
use Magento\Store\Model\ScopeInterface;
use Mollie\Api\Resources\Payment;
use stdClass;

class SetAddressesOnCart
{
    private const TELEPHONE_SHOW_PATH = 'customer/address/telephone_show';
    private const TELEPHONE_REQUIRED = 'req';
    private const TELEPHONE_PLACEHOLDER = '-';

    public function __construct(
        private readonly AddressInterfaceFactory $addressFactory,
        private readonly ScopeConfigInterface $scopeConfig,
    ) {}

    public function execute(CartInterface $oldCart, CartInterface $cart, Payment $payment): void
    {
        $shippingAddress = $this->getCompleteAddress($payment->shippingAddress);
        $billingAddress = $this->getCompleteAddress($payment->billingAddress);

        if ($shippingAddress === null || $billingAddress === null) {
            $this->copyAddressesFromOldCart($oldCart, $cart);
        }

        if ($shippingAddress !== null) {
            $this->setAddressOnCart(
                Address::ADDRESS_TYPE_SHIPPING,
                $cart,
                $shippingAddress,
                $oldCart->getShippingAddress()
            );
        }

        if ($billingAddress !== null) {
            $this->setAddressOnCart(
                Address::ADDRESS_TYPE_BILLING,
                $cart,
                $billingAddress,
                $oldCart->getBillingAddress()
            );
        }

        $email = $payment->billingAddress->email ?? $payment->shippingAddress->email ?? null;
        if (is_string($email) && $email !== '') {
            $cart->setCustomerEmail($email);
        }
    }

    /*
     * When only requiredCustomerDetails "email" is collected, Mollie returns a billingAddress that holds nothing
     * but the email. That is not an address we can ship to.
     */
    private function getCompleteAddress(?stdClass $address): ?stdClass
    {
        if ($address === null || !property_exists($address, 'givenName')) {
            return null;
        }

        return $address;
    }

    private function setAddressOnCart(
        string $type,
        CartInterface $cart,
        stdClass $address,
        ?AddressInterface $oldAddress
    ): void {
        $cartAddress = $this->addressFactory->create();
        $cartAddress->setFirstname($address->givenName);
        $cartAddress->setLastname($address->familyName);
        $cartAddress->setStreet(array_filter(
            [
                $address->streetAndNumber,
                $address->streetAdditional ?? null,
            ],
            static fn ($line): bool => $line !== null,
        ));
        $cartAddress->setPostcode($address->postalCode);
        $cartAddress->setTelephone($this->getTelephone($address, $oldAddress, (int)$cart->getStoreId()));
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

    /*
     * Mollie does not return a phone number for Express Components, while Magento requires one by default. Fall back
     * to the phone on the original cart, and to a placeholder so the paid order can still be created.
     */
    private function getTelephone(stdClass $address, ?AddressInterface $oldAddress, int $storeId): ?string
    {
        $telephone = $address->phone ?? $oldAddress?->getTelephone();
        if (is_string($telephone) && $telephone !== '') {
            return $telephone;
        }

        return $this->isTelephoneRequired($storeId) ? self::TELEPHONE_PLACEHOLDER : null;
    }

    private function isTelephoneRequired(int $storeId): bool
    {
        return $this->scopeConfig->getValue(self::TELEPHONE_SHOW_PATH, ScopeInterface::SCOPE_STORE, $storeId)
            === self::TELEPHONE_REQUIRED;
    }

    private function copyAddressesFromOldCart(CartInterface $oldCart, CartInterface $cart): void
    {
        $shipping = $oldCart->getShippingAddress();
        $cartAddress = $this->addressFactory->create();
        $data = $shipping->getData();
        unset($data['address_id'], $data['quote_id'], $data['created_at'], $data['updated_at']);
        $cartAddress->setData($data);
        $cart->setShippingAddress($shipping);

        $billing = $oldCart->getBillingAddress();
        $cartAddress = $this->addressFactory->create();
        $data = $billing->getData();
        unset($data['address_id'], $data['quote_id'], $data['created_at'], $data['updated_at']);
        $cartAddress->setData($data);
        $cart->setBillingAddress($billing);
    }
}
