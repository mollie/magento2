<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order\TransactionPart;

use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Mollie\Payment\Service\Order\TransactionPart\PhoneNumber;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class PhoneNumberTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @dataProvider convertsPhoneNumbersToTheCorrectFormatDataProvider
     * @param string $countryCode
     * @param string $phoneNumber
     * @param string $expected
     * @return void
     */
    #[DataProvider('convertsPhoneNumbersToTheCorrectFormatDataProvider')]
    public function testConvertsPhoneNumbersToTheCorrectFormat(
        string $countryCode,
        string $phoneNumber,
        string $expected,
    ): void {
        $order = $this->loadOrder('100000001');
        $order->setPayment($this->objectManager->create(OrderPaymentInterface::class));
        $order->getPayment()->setMethod('mollie_methods_in3');

        $billingAddress = $order->getBillingAddress();
        $billingAddress->setCountryId($countryCode);
        $billingAddress->setTelephone($phoneNumber);

        $shippingAddress = $order->getShippingAddress();
        $shippingAddress->setCountryId($countryCode);
        $shippingAddress->setTelephone($phoneNumber);

        /** @var PhoneNumber $instance */
        $instance = $this->objectManager->create(PhoneNumber::class);

        $transaction = $instance->process(
            $order,
            [
                'billingAddress' => [],
                'shippingAddress' => [],
            ],
        );

        $this->assertSame($expected, $transaction['billingAddress']['phone']);
        $this->assertSame($expected, $transaction['shippingAddress']['phone']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testDoesNotAddThePhoneNumberWhenItsEmpty(): void
    {
        $order = $this->loadOrder('100000001');
        $order->setPayment($this->objectManager->create(OrderPaymentInterface::class));
        $order->getPayment()->setMethod('mollie_methods_in3');

        $billingAddress = $order->getBillingAddress();
        $billingAddress->setCountryId('NL');
        $billingAddress->setTelephone('');

        $shippingAddress = $order->getShippingAddress();
        $shippingAddress->setCountryId('NL');
        $shippingAddress->setTelephone('');

        /** @var PhoneNumber $instance */
        $instance = $this->objectManager->create(PhoneNumber::class);

        $transaction = $instance->process(
            $order,
            [
                'billingAddress' => [],
                'shippingAddress' => [],
            ],
        );

        $this->assertArrayNotHasKey('phone', $transaction['billingAddress']);
        $this->assertArrayNotHasKey('phone', $transaction['shippingAddress']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testHandlePhoneNumbersForVirtualProductOrders(): void {
        $order = $this->loadOrder('100000001');
        $order->setPayment($this->objectManager->create(OrderPaymentInterface::class));
        $order->getPayment()->setMethod('mollie_methods_in3');

        $billingAddress = $order->getBillingAddress();
        $billingAddress->setCountryId('NL');
        $billingAddress->setTelephone('06 1234 5678');

        /** @var PhoneNumber $instance */
        $instance = $this->objectManager->create(PhoneNumber::class);

        $transaction = $instance->process(
            $order,
            [
                'billingAddress' => [],
            // Virtual orders don't have a shipping address
//                'shippingAddress' => [],
            ]
        );

        $this->assertSame('+31612345678', $transaction['billingAddress']['phone']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testHandlesNullAsPhonenumber(): void
    {
        $order = $this->loadOrder('100000001');
        $order->setPayment($this->objectManager->create(OrderPaymentInterface::class));
        $order->getPayment()->setMethod('mollie_methods_in3');

        $billingAddress = $order->getBillingAddress();
        $billingAddress->setCountryId('NL');
        $billingAddress->setData(OrderAddressInterface::TELEPHONE, null);

        $shippingAddress = $order->getShippingAddress();
        $shippingAddress->setCountryId('NL');
        $shippingAddress->setData(OrderAddressInterface::TELEPHONE, null);

        /** @var PhoneNumber $instance */
        $instance = $this->objectManager->create(PhoneNumber::class);

        $transaction = $instance->process(
            $order,
            [
                'billingAddress' => [],
                'shippingAddress' => [],
            ],
        );

        $this->assertArrayNotHasKey('phone', $transaction['billingAddress']);
        $this->assertArrayNotHasKey('phone', $transaction['shippingAddress']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testDoesNotThrowWhenThePhoneNumberIsInvalid(): void
    {
        $order = $this->loadOrder('100000001');
        $order->setPayment($this->objectManager->create(OrderPaymentInterface::class));
        $order->getPayment()->setMethod('mollie_methods_in3');

        $billingAddress = $order->getBillingAddress();
        $billingAddress->setCountryId('NL');
        $billingAddress->setTelephone('000');

        $shippingAddress = $order->getShippingAddress();
        $shippingAddress->setCountryId('NL');
        $shippingAddress->setTelephone('000');

        /** @var PhoneNumber $instance */
        $instance = $this->objectManager->create(PhoneNumber::class);

        $transaction = $instance->process(
            $order,
            [
                'billingAddress' => [],
                'shippingAddress' => [],
            ],
        );

        $this->assertArrayNotHasKey('phone', $transaction['billingAddress']);
        $this->assertArrayNotHasKey('phone', $transaction['shippingAddress']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @dataProvider doesNotSendInvalidPhoneNumbersDataProvider
     */
    #[DataProvider('doesNotSendInvalidPhoneNumbersDataProvider')]
    public function testDoesNotSendInvalidPhoneNumbers(string $countryCode, string $phoneNumber): void
    {
        $order = $this->loadOrder('100000001');
        $order->setPayment($this->objectManager->create(OrderPaymentInterface::class));
        $order->getPayment()->setMethod('mollie_methods_ideal');

        $order->getBillingAddress()->setCountryId($countryCode)->setTelephone($phoneNumber);
        $order->getShippingAddress()->setCountryId($countryCode)->setTelephone($phoneNumber);

        /** @var PhoneNumber $instance */
        $instance = $this->objectManager->create(PhoneNumber::class);

        $transaction = $instance->process(
            $order,
            [
                'billingAddress' => [],
                'shippingAddress' => [],
            ],
        );

        $this->assertArrayNotHasKey('phone', $transaction['billingAddress']);
        $this->assertArrayNotHasKey('phone', $transaction['shippingAddress']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testDoesNotOverrideExistingAddressData(): void
    {
        $order = $this->loadOrder('100000001');
        $order->setPayment($this->objectManager->create(OrderPaymentInterface::class));
        $order->getPayment()->setMethod('mollie_methods_in3');

        $billingAddress = $order->getBillingAddress();
        $billingAddress->setCountryId('NL');
        $billingAddress->setData(OrderAddressInterface::TELEPHONE, '0612345678');

        $shippingAddress = $order->getShippingAddress();
        $shippingAddress->setCountryId('NL');
        $shippingAddress->setData(OrderAddressInterface::TELEPHONE, '0612345678');

        /** @var PhoneNumber $instance */
        $instance = $this->objectManager->create(PhoneNumber::class);

        $transaction = $instance->process(
            $order,
            [
                'billingAddress' => [
                    'streetAndNumber' => 'Example Street 15',
                ],
                'shippingAddress' => [
                    'streetAndNumber' => 'Example Street 15',
                ],
            ],
        );

        $this->assertArrayHasKey('streetAndNumber', $transaction['billingAddress']);
        $this->assertSame('Example Street 15', $transaction['billingAddress']['streetAndNumber']);
        $this->assertArrayHasKey('phone', $transaction['billingAddress']);

        $this->assertArrayHasKey('streetAndNumber', $transaction['shippingAddress']);
        $this->assertSame('Example Street 15', $transaction['shippingAddress']['streetAndNumber']);
        $this->assertArrayHasKey('phone', $transaction['shippingAddress']);
    }

    public static function convertsPhoneNumbersToTheCorrectFormatDataProvider(): array
    {
        return [
            // The Netherlands (NL)
            ['NL', '06 1234 5678', '+31612345678'],
            ['NL', '010 123 4567', '+31101234567'],
            ['NL', '020 987 6543', '+31209876543'],

            ['NL', '00316-12 34 56 78', '+31612345678'],
            ['NL', '+316-12 34 56 78', '+31612345678'],
            ['NL', ' 06-12 34 56 78', '+31612345678'],
            ['NL', '010-123 4567', '+31101234567'],

            // United States (US)
            ['US', '(555) 123-4567', '+15551234567'],
            ['US', '(212) 123-4567', '+12121234567'],
            ['US', '(415) 987-6543', '+14159876543'],

            ['US', '001 (415) 987-6543', '+14159876543'],
            ['US', '+1 (415) 987-6543', '+14159876543'],
            ['US', '001(415) 987-6543', '+14159876543'],
            ['US', '+1(415) 987-6543', '+14159876543'],

            // United Kingdom (GB)
            ['GB', '020 7123 4567', '+442071234567'],
            ['GB', '0161 123 4567', '+441611234567'],
            ['GB', '0131 987 6543', '+441319876543'],

            // Australia (AU)
            ['AU', '04 1234 5678', '+61412345678'],
            ['AU', '02 1234 5678', '+61212345678'],
            ['AU', '03 9876 5432', '+61398765432'],

            // Germany (DE)
            ['DE', '030 1234 5678', '+493012345678'],
            ['DE', '040 1234 5678', '+494012345678'],
            ['DE', '089 9876 5432', '+498998765432'],

            // Foreign numbers keep their own country code
            ['BE', '+31612345678', '+31612345678'],
            ['BE', '0031612345678', '+31612345678'],
            ['BE', '+31 6 1234 5678', '+31612345678'],
            ['DE', '+44 20 7123 4567', '+442071234567'],
            ['NL', '+32 470 12 34 56', '+32470123456'],

            // Trunk prefix written after the country code
            ['NL', '+31 (0)6 1234 5678', '+31612345678'],

            // Other common European formats
            ['BE', '0470 12 34 56', '+32470123456'],
            ['FR', '06 12 34 56 78', '+33612345678'],
            ['ES', '612 34 56 78', '+34612345678'],
            ['AT', '0664 123456', '+43664123456'],
            ['CH', '078 123 45 67', '+41781234567'],
            ['PL', '512 345 678', '+48512345678'],
            ['SE', '070-123 45 67', '+46701234567'],
            ['IE', '085 012 3456', '+353850123456'],
            ['GB', '07400 123456', '+447400123456'],

            // Short landlines
            ['AT', '01 113', '+431113'],
            ['DE', '030 136', '+4930136'],
            ['LU', '24 79', '+3522479'],

            // The leading zero is part of the number in Italy, San Marino and Vatican City
            ['IT', '02 1234 5678', '+390212345678'],
            ['IT', '+39 02 1234 5678', '+390212345678'],
            ['IT', '312 345 6789', '+393123456789'],
            ['SM', '0549 886377', '+3780549886377'],
            ['VA', '06 6981 2345', '+390669812345'],
            ['NL', '+39 02 1234 5678', '+390212345678'],

            // Hungary uses 06 as trunk prefix
            ['HU', '06 20 123 4567', '+36201234567'],
            ['HU', '06 1 234 5678', '+3612345678'],
            ['HU', '+36 20 123 4567', '+36201234567'],
        ];
    }

    public static function doesNotSendInvalidPhoneNumbersDataProvider(): array
    {
        return [
            'too short with own country code' => ['NL', '+319'],
            'only a country code digit' => ['NL', '+3'],
            'too short national number' => ['NL', '0612'],
            'too short foreign number' => ['BE', '+3161'],
            'too long foreign number' => ['BE', '+31 6123456789012345'],
            'no digits' => ['NL', 'unknown'],
        ];
    }
}
