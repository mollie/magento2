<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Quote;

use Magento\Customer\Model\AddressFactory;
use Magento\Framework\Validator\Factory as ValidatorFactory;
use Magento\Quote\Api\Data\AddressInterface;

class SetCityFromApplePayAddress
{
    private const UNSUPPORTED_CHARACTERS = '/[^\p{L}\p{M}\s\'-]/u';
    private const CONSECUTIVE_WHITESPACE = '/\s+/u';
    private const TYPOGRAPHIC_APOSTROPHE = '’';
    private const VALIDATOR_ENTITY = 'customer_address';
    private const VALIDATOR_GROUP = 'save';

    public function __construct(
        private readonly ValidatorFactory $validatorFactory,
        private readonly AddressFactory $addressFactory,
    ) {
    }

    public function execute(AddressInterface $address, array $input): void
    {
        $address->setCity($this->getSupportedCity($input['locality'] ?? ''));
    }

    private function getSupportedCity(string $city): string
    {
        if (!$this->isRejectedByMagento($city)) {
            return $city;
        }

        $supported = $this->removeUnsupportedCharacters($city);

        if ($supported === '' || $this->isRejectedByMagento($supported)) {
            return $city;
        }

        return $supported;
    }

    private function removeUnsupportedCharacters(string $city): string
    {
        return trim(
            (string)preg_replace(
                self::CONSECUTIVE_WHITESPACE,
                ' ',
                (string)preg_replace(
                    self::UNSUPPORTED_CHARACTERS,
                    ' ',
                    str_replace(self::TYPOGRAPHIC_APOSTROPHE, "'", $city),
                ),
            ),
        );
    }

    private function isRejectedByMagento(string $city): bool
    {
        $address = $this->addressFactory->create();
        $address->addData([
            AddressInterface::KEY_FIRSTNAME => 'Apple',
            AddressInterface::KEY_LASTNAME => 'Pay',
            AddressInterface::KEY_STREET => ['1'],
            AddressInterface::KEY_CITY => $city,
            AddressInterface::KEY_POSTCODE => '1',
            AddressInterface::KEY_TELEPHONE => '1',
            AddressInterface::KEY_COUNTRY_ID => 'NL',
        ]);

        $validator = $this->validatorFactory->createValidator(self::VALIDATOR_ENTITY, self::VALIDATOR_GROUP);

        if ($validator->isValid($address)) {
            return false;
        }

        return $this->hasCityMessage($validator->getMessages());
    }

    private function hasCityMessage(array $messages): bool
    {
        if (array_key_exists(AddressInterface::KEY_CITY, $messages)) {
            return true;
        }

        return array_filter(
            $messages,
            static fn ($message): bool => is_array($message)
                && array_key_exists(AddressInterface::KEY_CITY, $message),
        ) !== [];
    }
}
