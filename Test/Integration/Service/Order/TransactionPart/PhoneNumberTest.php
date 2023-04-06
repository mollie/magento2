<?php

namespace Mollie\Payment\Test\Integration\Service\Order\TransactionPart;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Model\Client\Orders;
use Mollie\Payment\Model\Client\Payments;
use Mollie\Payment\Service\Order\TransactionPart\PhoneNumber;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class PhoneNumberTest extends IntegrationTestCase
{
    public function testDoesNothingWhenPaymentsApiIsUsed(): void
    {
        $transaction = [];

        /** @var PhoneNumber $instance */
        $instance = $this->objectManager->create(PhoneNumber::class);

        $newTransaction = $instance->process(
            $this->objectManager->create(OrderInterface::class),
            Payments::CHECKOUT_TYPE,
            $transaction
        );

        $this->assertSame($transaction, $newTransaction);
    }

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
        string $expected
    ): void {
        $order = $this->loadOrder('100000001');
        $billingAddress = $order->getBillingAddress();
        $billingAddress->setCountryId($countryCode);
        $billingAddress->setTelephone($phoneNumber);

        /** @var PhoneNumber $instance */
        $instance = $this->objectManager->create(PhoneNumber::class);

        $transaction = $instance->process(
            $order,
            Orders::CHECKOUT_TYPE,
            ['billingAddress' => []]
        );

        $this->assertSame($expected, $transaction['billingAddress']['phone']);
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
