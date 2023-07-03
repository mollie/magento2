<?php

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order\Invoice;

use Mollie\Payment\Service\Order\Invoice\ShouldEmailInvoice;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class ShouldEmailInvoiceTest extends IntegrationTestCase
{
    /**
     * @magentoConfigFixture default_store payment/mollie_general/invoice_notify 0
     *
     * @return void
     */
    public function testReturnsFalseWhenInvoiceSendingIsDisabled(): void
    {
        /** @var ShouldEmailInvoice $instance */
        $instance = $this->objectManager->create(ShouldEmailInvoice::class);

        $result = $instance->execute(1, 'mollie_methods_ideal');

        $this->assertFalse($result);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/invoice_notify 1
     *
     * @return void
     */
    public function testShouldReturnTrueWhenInvoiceSendingEnabledButNowKlarna(): void
    {
        /** @var ShouldEmailInvoice $instance */
        $instance = $this->objectManager->create(ShouldEmailInvoice::class);

        $result = $instance->execute(1, 'mollie_methods_ideal');

        $this->assertTrue($result);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/invoice_notify 1
     * @magentoConfigFixture default_store payment/mollie_general/invoice_notify_klarna 1
     *
     * @return void
     */
    public function testShouldReturnTrueWhenEnabledAndMethodIsKlarnaAndKlarnaInvoiceIsEnabled(): void
    {
        /** @var ShouldEmailInvoice $instance */
        $instance = $this->objectManager->create(ShouldEmailInvoice::class);

        $result = $instance->execute(1, 'mollie_methods_klarnapaynow');

        $this->assertTrue($result);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/invoice_notify 1
     * @magentoConfigFixture default_store payment/mollie_general/invoice_notify_klarna 0
     *
     * @return void
     */
    public function testShouldReturnFalseWhenEnabledAndMethodIsKlarnaAndKlarnaInvoiceIsDisabled(): void
    {
        /** @var ShouldEmailInvoice $instance */
        $instance = $this->objectManager->create(ShouldEmailInvoice::class);

        $result = $instance->execute(1, 'mollie_methods_klarnapaynow');

        $this->assertFalse($result);
    }
}
