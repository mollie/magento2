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

    public function convertsPhoneNumbersToTheCorrectFormatDataProvider(): array
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
        ];
    }
}
