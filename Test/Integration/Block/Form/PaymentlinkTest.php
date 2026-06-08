<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Block\Form;

use DateInterval;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Mollie\Payment\Block\Form\Paymentlink;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class PaymentlinkTest extends IntegrationTestCase
{
    /**
     * @magentoConfigFixture current_store payment/mollie_methods_paymentlink/days_before_expire 10
     */
    public function testReturnsTheCorrectDate(): void
    {
        /** @var Paymentlink $instance */
        $instance = $this->objectManager->create(Paymentlink::class);

        $now = $this->objectManager->create(TimezoneInterface::class)->scopeDate(null);
        $expected = $now->add(new DateInterval('P10D'));

        $this->assertEquals($expected->format('Y-m-d'), $instance->getExpiresAt());
    }

    /**
     * @magentoConfigFixture current_store payment/mollie_methods_paymentlink/days_before_expire
     */
    public function testAnEmptyStringWhenNoConfigAvailable(): void
    {
        /** @var Paymentlink $instance */
        $instance = $this->objectManager->create(Paymentlink::class);

        $this->assertEmpty($instance->getExpiresAt());
    }
}
