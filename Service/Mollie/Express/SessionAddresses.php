<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Express;

use InvalidArgumentException;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\AddressInterface as QuoteAddress;
use Magento\Quote\Api\Data\CartInterface;
use Mollie\Payment\Service\Format\E164PhoneNumber;

/**
 * The addresses Magento already knows for the express session, in the format of the Mollie sessions API. The quote
 * address wins over the customer's default address, because the customer entered it for this order.
 */
class SessionAddresses
{
    private const REQUIRED_FIELDS = ['givenName', 'familyName', 'streetAndNumber', 'postalCode', 'city', 'country'];

    public function __construct(
        private readonly AddressRepositoryInterface $addressRepository,
        private readonly E164PhoneNumber $e164PhoneNumber,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function billing(CartInterface $cart): array
    {
        $fromQuote = $this->fromQuoteAddress($cart->getBillingAddress());
        if ($fromQuote !== []) {
            return $fromQuote;
        }

        return $this->fromDefaultAddress($cart->getCustomer()->getDefaultBilling());
    }

    /**
     * @return array<string, string>
     */
    public function shipping(CartInterface $cart): array
    {
        $fromQuote = $this->fromQuoteAddress($cart->getShippingAddress());
        if ($fromQuote !== []) {
            return $fromQuote;
        }

        return $this->fromDefaultAddress($cart->getCustomer()->getDefaultShipping());
    }

    /**
     * @return array<string, string>
     */
    private function fromQuoteAddress(?QuoteAddress $address): array
    {
        if ($address === null) {
            return [];
        }

        return $this->sendable([
            'givenName' => (string)$address->getFirstname(),
            'familyName' => (string)$address->getLastname(),
            'organizationName' => (string)$address->getCompany(),
            'streetAndNumber' => $this->streetLine($address->getStreet(), 0),
            'streetAdditional' => $this->streetLine($address->getStreet(), 1),
            'postalCode' => (string)$address->getPostcode(),
            'phone' => $this->phone((string)$address->getCountryId(), (string)$address->getTelephone()),
            'city' => (string)$address->getCity(),
            'region' => (string)$address->getRegionCode(),
            'country' => (string)$address->getCountryId(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    private function fromDefaultAddress(?string $addressId): array
    {
        if ($addressId === null || $addressId === '') {
            return [];
        }

        try {
            $address = $this->addressRepository->getById((int)$addressId);
        } catch (LocalizedException) {
            return [];
        }

        return $this->sendable([
            'givenName' => (string)$address->getFirstname(),
            'familyName' => (string)$address->getLastname(),
            'organizationName' => (string)$address->getCompany(),
            'streetAndNumber' => $this->streetLine($address->getStreet(), 0),
            'streetAdditional' => $this->streetLine($address->getStreet(), 1),
            'postalCode' => (string)$address->getPostcode(),
            'phone' => $this->phone((string)$address->getCountryId(), (string)$address->getTelephone()),
            'city' => (string)$address->getCity(),
            'region' => (string)($address->getRegion()?->getRegionCode() ?? ''),
            'country' => (string)$address->getCountryId(),
        ]);
    }

    /**
     * Mollie only accepts phone numbers in the international E.164 format, and the webhook has its own telephone
     * fallback, so an address without a usable phone is still worth sending.
     */
    private function phone(string $countryCodeIso2, string $telephone): string
    {
        if ($countryCodeIso2 === '' || $telephone === '') {
            return '';
        }

        try {
            return $this->e164PhoneNumber->format($countryCodeIso2, $telephone);
        } catch (InvalidArgumentException) {
            return '';
        }
    }

    /**
     * @param array<string, string> $candidate
     * @return array<string, string>
     */
    private function sendable(array $candidate): array
    {
        $filled = array_filter($candidate, static fn (string $value): bool => $value !== '');

        if (array_diff(self::REQUIRED_FIELDS, array_keys($filled)) !== []) {
            return [];
        }

        return $filled;
    }

    /**
     * @param string[]|null $street
     */
    private function streetLine(?array $street, int $line): string
    {
        return trim((string)($street[$line] ?? ''));
    }
}
