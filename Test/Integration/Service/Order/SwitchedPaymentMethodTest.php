<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order;

use Mollie\Payment\Service\Order\SwitchedPaymentMethod;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class SwitchedPaymentMethodTest extends IntegrationTestCase
{
    public function testReturnsTheMollieMethodWhenTheCustomerSwitchedOnTheMolliePaymentPage(): void
    {
        $instance = $this->objectManager->create(SwitchedPaymentMethod::class);

        $result = $instance->execute('mollie_methods_banktransfer', 'paypal');

        $this->assertSame('paypal', $result);
    }

    public function testReturnsTheMollieMethodForAPaymentLinkOrder(): void
    {
        $instance = $this->objectManager->create(SwitchedPaymentMethod::class);

        $result = $instance->execute('mollie_methods_paymentlink', 'ideal');

        $this->assertSame('ideal', $result);
    }

    public function testReturnsNullWhenMollieHasNotReportedAMethodYet(): void
    {
        $instance = $this->objectManager->create(SwitchedPaymentMethod::class);

        $result = $instance->execute('mollie_methods_banktransfer', null);

        $this->assertNull($result);
    }

    public static function unchangedMethods(): array
    {
        return [
            'same method' => ['mollie_methods_banktransfer', 'banktransfer'],
            'google pay is processed as credit card' => ['mollie_methods_googlepay', 'creditcard'],
            'klarna pay later is reported as klarna' => ['mollie_methods_klarnapaylater', 'klarna'],
            'klarna pay now is reported as klarna' => ['mollie_methods_klarnapaynow', 'klarna'],
            'klarna slice it is reported as klarna' => ['mollie_methods_klarnasliceit', 'klarna'],
            'gift card remainder has its own breakdown' => ['mollie_methods_giftcard', 'ideal'],
            'voucher remainder has its own breakdown' => ['mollie_methods_voucher', 'ideal'],
        ];
    }

    /**
     * @dataProvider unchangedMethods
     */
    #[DataProvider('unchangedMethods')]
    public function testReturnsNullWhenThePaymentMethodDidNotChange(string $magentoMethod, string $mollieMethod): void
    {
        $instance = $this->objectManager->create(SwitchedPaymentMethod::class);

        $result = $instance->execute($magentoMethod, $mollieMethod);

        $this->assertNull($result);
    }
}
