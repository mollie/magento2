<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order\TransactionPart;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Service\Order\TransactionPart\HandleUnusablePostalCode;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class HandleUnusablePostalCodeTest extends IntegrationTestCase
{
    private const PERSONAL_DETAILS = [
        'givenName' => 'Aoife',
        'familyName' => 'Murphy',
        'organizationName' => 'Acme',
        'email' => 'aoife@example.com',
        'phone' => '+353861234567',
    ];

    public function testKeepsOnlyThePersonalDetailsOfABillingAddressWithoutPostalCode(): void
    {
        $result = $this->process(['billingAddress' => $this->addressWithPostalCode('-')]);

        $this->assertSame(self::PERSONAL_DETAILS, $result['billingAddress']);
    }

    public function testLeavesOutAShippingAddressWithoutPostalCode(): void
    {
        $result = $this->process([
            'billingAddress' => $this->addressWithPostalCode('D02 X285'),
            'shippingAddress' => $this->addressWithPostalCode(null),
        ]);

        $this->assertSame($this->addressWithPostalCode('D02 X285'), $result['billingAddress']);
        $this->assertArrayNotHasKey('shippingAddress', $result);
    }

    public function testKeepsTheShippingAddressWhenOnlyTheBillingAddressHasNoPostalCode(): void
    {
        $result = $this->process([
            'billingAddress' => $this->addressWithPostalCode(''),
            'shippingAddress' => $this->addressWithPostalCode('D02 X285'),
        ]);

        $this->assertSame(self::PERSONAL_DETAILS, $result['billingAddress']);
        $this->assertSame($this->addressWithPostalCode('D02 X285'), $result['shippingAddress']);
    }

    public function testKeepsTheAddressesWithoutPostalCodeInACountryMollieAcceptsWithoutPostalCode(): void
    {
        $result = $this->process([
            'billingAddress' => $this->addressWithPostalCode(null, 'HK'),
            'shippingAddress' => $this->addressWithPostalCode('-', 'HK'),
        ]);

        $expected = array_diff_key($this->addressWithPostalCode(null, 'HK'), ['postalCode' => true]);
        $this->assertSame($expected, $result['billingAddress']);
        $this->assertSame($expected, $result['shippingAddress']);
    }

    public function testKeepsAUsablePostalCodeInACountryMollieAcceptsWithoutPostalCode(): void
    {
        $result = $this->process(['billingAddress' => $this->addressWithPostalCode('999077', 'HK')]);

        $this->assertSame($this->addressWithPostalCode('999077', 'HK'), $result['billingAddress']);
    }

    public function testDoesNotAddAddressesToATransactionWithoutAddresses(): void
    {
        $result = $this->process(['method' => 'creditcard']);

        $this->assertSame(['method' => 'creditcard'], $result);
    }

    /**
     * @param array<string, mixed> $transaction
     * @return array<string, mixed>
     */
    private function process(array $transaction): array
    {
        return $this->objectManager->create(HandleUnusablePostalCode::class)->process(
            $this->objectManager->create(OrderInterface::class),
            $transaction,
        );
    }

    /**
     * @return array<string, string|null>
     */
    private function addressWithPostalCode(?string $postalCode, string $country = 'IE'): array
    {
        return [
            ...self::PERSONAL_DETAILS,
            'streetAndNumber' => '1 Grafton Street',
            'postalCode' => $postalCode,
            'city' => 'Dublin',
            'region' => 'Leinster',
            'country' => $country,
        ];
    }
}
