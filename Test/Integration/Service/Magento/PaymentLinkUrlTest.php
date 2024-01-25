<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Magento;

use Mollie\Payment\Service\Magento\PaymentLinkUrl;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class PaymentLinkUrlTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testReturnsControllerUrlByDefault(): void
    {
        $order = $this->loadOrder('100000001');

        /** @var PaymentLinkUrl $instance */
        $instance = $this->objectManager->create(PaymentLinkUrl::class);

        $result = $instance->execute((int)$order->getEntityId());

        $this->assertStringContainsString('/mollie/checkout/paymentlink/order/', $result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_general/use_custom_paymentlink_url 1
     * @magentoConfigFixture default_store payment/mollie_general/custom_paymentlink_url https://example.com
     * @return void
     */
    public function testUsesTheCustomPaymentLinkUrl(): void
    {
        $order = $this->loadOrder('100000001');

        /** @var PaymentLinkUrl $instance */
        $instance = $this->objectManager->create(PaymentLinkUrl::class);

        $result = $instance->execute((int)$order->getEntityId());

        $this->assertStringContainsString('https://example.com', $result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_general/use_custom_paymentlink_url 1
     * @magentoConfigFixture default_store payment/mollie_general/custom_paymentlink_url https://example.com/?order={{order}}
     * @return void
     */
    public function testReplacesThePlaceholderWhenAvailable(): void
    {
        $order = $this->loadOrder('100000001');

        /** @var PaymentLinkUrl $instance */
        $instance = $this->objectManager->create(PaymentLinkUrl::class);

        $result = $instance->execute((int)$order->getEntityId());

        $this->assertStringContainsString('https://example.com/?order=', $result);
        $this->assertStringNotContainsString('{{order}}', $result);
    }
}
