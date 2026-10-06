<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Checkout;

use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Mollie\Payment\Service\Checkout\PaymentInstructions;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class PaymentInstructionsTest extends IntegrationTestCase
{
    public function testSupportsBanktransferAndMultibancoOnly(): void
    {
        $instance = $this->objectManager->create(PaymentInstructions::class);

        $result = array_map($instance->isSupported(...), [
            'mollie_methods_banktransfer',
            'mollie_methods_multibanco',
            'mollie_methods_ideal',
        ]);

        $this->assertSame([true, true, false], $result);
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testEscapesHtmlAndConvertsLineBreaks(): void
    {
        $this->setStoreConfig('payment/mollie_methods_banktransfer/instructions', "<b>Pay first</b>\nThen we ship");
        $this->setStoreConfig('payment/mollie_methods_banktransfer/instructions_style', 'info');
        $instance = $this->objectManager->create(PaymentInstructions::class);

        $result = $instance->getHtml('mollie_methods_banktransfer', 1);

        $this->assertSame("&lt;b&gt;Pay first&lt;/b&gt;<br />\nThen we ship", $result);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/instructions Should not be shown.
     */
    public function testReturnsNoHtmlForUnsupportedMethods(): void
    {
        $instance = $this->objectManager->create(PaymentInstructions::class);

        $result = $instance->getHtml('mollie_methods_ideal', 1);

        $this->assertSame('', $result);
    }

    /**
     * @magentoAppIsolation enabled
     */
    public function testReturnsNoHtmlWhenTheStyleIsNoInstructions(): void
    {
        $this->setStoreConfig('payment/mollie_methods_banktransfer/instructions', 'Please pay before we ship.');
        $this->setStoreConfig('payment/mollie_methods_banktransfer/instructions_style', 'none');
        $instance = $this->objectManager->create(PaymentInstructions::class);

        $result = [
            $instance->isEnabled('mollie_methods_banktransfer', 1),
            $instance->getHtml('mollie_methods_banktransfer', 1),
        ];

        $this->assertSame([false, ''], $result);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function styleProvider(): array
    {
        return [
            'plain' => ['plain', ''],
            'info' => ['info', 'message info'],
            'warning' => ['warning', 'message warning'],
            'success' => ['success', 'message success'],
            'unknown falls back to info' => ['does-not-exist', 'message info'],
        ];
    }

    /**
     * @magentoAppIsolation enabled
     * @dataProvider styleProvider
     */
    #[DataProvider('styleProvider')]
    public function testReturnsTheCssClassForTheConfiguredStyle(string $style, string $expectedCssClass): void
    {
        $this->setStoreConfig('payment/mollie_methods_multibanco/instructions_style', $style);
        $instance = $this->objectManager->create(PaymentInstructions::class);

        $result = $instance->getCssClass('mollie_methods_multibanco', 1);

        $this->assertSame($expectedCssClass, $result);
    }

    private function setStoreConfig(string $path, string $value): void
    {
        $this->objectManager->get(MutableScopeConfigInterface::class)->setValue(
            $path,
            $value,
            ScopeInterface::SCOPE_STORE,
            'default',
        );
    }
}
