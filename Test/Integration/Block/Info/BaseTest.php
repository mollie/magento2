<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Block\Info;

use Magento\Sales\Model\Order\Payment\Info;
use Mollie\Payment\Block\Info\Base;
use Mollie\Payment\Service\Magento\PaymentLinkUrl;
use Mollie\Payment\Test\Fakes\Service\Magento\PaymentLinkUrlFake;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class BaseTest extends IntegrationTestCase
{
    public function testReturnsTheDashboardUrl()
    {
        /** @var Info $info */
        $info = $this->objectManager->create(Info::class);
        $info->setAdditionalInformation('dashboard_url', 'http://example.com/dashboard');

        /** @var Base $instance */
        $instance = $this->objectManager->create(Base::class);
        $instance->setData('info', $info);
        $this->assertEquals('http://example.com/dashboard', $instance->getDashboardUrl());
    }

    public function testReturnsTheMollieId()
    {
        /** @var Info $info */
        $info = $this->objectManager->create(Info::class);
        $info->setAdditionalInformation('mollie_id', 'ord_123abc');

        /** @var Base $instance */
        $instance = $this->objectManager->create(Base::class);
        $instance->setData('info', $info);
        $this->assertEquals('ord_123abc', $instance->getMollieId());
    }

    public function returnsNullWhenInfoIsNotAvailable()
    {
        return [
            ['getDashboardUrl'],
            ['getMollieId'],
        ];
    }

    /**
     * @dataProvider returnsNullWhenInfoIsNotAvailable
     */
    public function testReturnsNullWhenInfoIsNotAvailable($method)
    {
        /** @var Base $instance */
        $instance = $this->objectManager->create(Base::class);
        $this->assertNull($instance->{$method}());
    }

    public function testReturnsTheRemainderAmount()
    {
        /** @var Info $info */
        $info = $this->objectManager->create(Info::class);
        $info->setAdditionalInformation('remainder_amount', '100');

        /** @var Base $instance */
        $instance = $this->objectManager->create(Base::class);
        $instance->setData('info', $info);
        $this->assertEquals('100', $instance->getRemainderAmount());
    }

    /**
     * @magentoConfigFixture current_store payment/mollie_methods_paymentlink/add_message 1
     * @magentoConfigFixture current_store payment/mollie_methods_paymentlink/use_custom_email_template 0
     * @magentoConfigFixture current_store payment/mollie_methods_paymentlink/message "Click <a href="%link%">here</a> to complete your payment"
     * @return void
     */
    public function testReturnsThePaymentLinkMessageWithLink(): void
    {
        $paymentLinkFake = $this->objectManager->get(PaymentLinkUrlFake::class);
        $paymentLinkFake->setUrl('http://www.example.com/mollie/checkout/paymentlink/order/-999/');
        $this->objectManager->addSharedInstance($paymentLinkFake, PaymentLinkUrl::class);

        /** @var Info $info */
        $info = $this->objectManager->create(Info::class);

        /** @var Base $instance */
        $instance = $this->objectManager->create(Base::class);
        $instance->setData('info', $info);

        $result = $instance->getPaymentLink();

        $this->assertStringNotContainsString('%link%', $result);
    }

    /**
     * @magentoConfigFixture current_store payment/mollie_methods_paymentlink/add_message 1
     * @magentoConfigFixture current_store payment/mollie_methods_paymentlink/use_custom_email_template 1
     * @magentoConfigFixture current_store payment/mollie_methods_paymentlink/message "Click <a href="%link%">here</a> to complete your payment"
     * @return void
     */
    public function testReturnsNothingWhenPaymentLinkMailIsActive(): void
    {
        $paymentLinkFake = $this->objectManager->get(PaymentLinkUrlFake::class);
        $paymentLinkFake->shouldNotBeCalled();
        $this->objectManager->addSharedInstance($paymentLinkFake, PaymentLinkUrl::class);

        /** @var Info $info */
        $info = $this->objectManager->create(Info::class);

        /** @var Base $instance */
        $instance = $this->objectManager->create(Base::class);
        $instance->setData('info', $info);

        $result = $instance->getPaymentLink();

        $this->assertNull($result);
    }
}
