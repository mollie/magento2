<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\Encryptor;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Service\Order\Transaction;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class TransactionTest extends IntegrationTestCase
{
    /**
     * @magentoConfigFixture current_store payment/mollie_general/use_custom_redirect_url 0
     */
    public function testRedirectUrlWithoutCustomUrl(): void
    {
        /** @var Transaction $instance */
        $instance = $this->objectManager->create(Transaction::class);

        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);
        $order->setId(9999);

        $result = $instance->getRedirectUrl($order, 'paymenttoken');

        $this->assertStringContainsString('mollie/checkout/process', $result);
    }

    /**
     * @magentoConfigFixture current_store payment/mollie_general/use_custom_redirect_url 1
     * @magentoConfigFixture current_store payment/mollie_general/custom_redirect_url
     */
    public function testRedirectUrlWithEmptyCustomUrl(): void
    {
        /** @var Transaction $instance */
        $instance = $this->objectManager->create(Transaction::class);

        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);
        $order->setId(9999);

        $result = $instance->getRedirectUrl($order, 'paymenttoken');

        $this->assertStringContainsString('mollie/checkout/process', $result);
    }

    /**
     * @magentoConfigFixture current_store payment/mollie_general/use_custom_redirect_url 1
     * @magentoConfigFixture current_store payment/mollie_general/custom_redirect_url https://www.mollie.com
     */
    public function testRedirectUrlWithFilledCustomUrl(): void
    {
        /** @var Transaction $instance */
        $instance = $this->objectManager->create(Transaction::class);

        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);
        $order->setId(9999);

        $result = $instance->getRedirectUrl($order, 'paymenttoken');

        $this->assertStringContainsString('https://www.mollie.com', $result);
    }

    /**
     * @magentoConfigFixture current_store payment/mollie_general/use_custom_redirect_url 1
     * @magentoConfigFixture current_store payment/mollie_general/custom_redirect_url https://www.mollie.com/?order_id={{ORDER_ID}}&payment_token={{PAYMENT_TOKEN}}&increment_id={{INCREMENT_ID}}&short_base_url={{base_url}}&unsecure_base_url={{unsecure_base_url}}&secure_base_url={{secure_base_url}}
     */
    public function testAppendsTheParamsToTheUrl(): void
    {
        $configMock = $this->createMock(ScopeConfigInterface::class);

        $callIndex = 0;
        $configMock
            ->method('getValue')
            ->willReturnCallback(function (string $path) use (&$callIndex): string {
                $callIndex++;
                if ($callIndex == 1 && $path == 'web/unsecure/base_url') {
                    return 'http://base_url.test/';
                }

                if ($path == 'web/unsecure/base_url') {
                    return 'http://unsecure_base_url.test/';
                }

                if ($path == 'web/secure/base_url') {
                    return 'https://secure_base_url.test/';
                }

                throw new Exception('Unexpected path: ' . $path . ' ' . $callIndex);
            });

        /** @var Transaction $instance */
        $instance = $this->objectManager->create(Transaction::class, [
            'scopeConfig' => $configMock,
        ]);

        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);
        $order->setId(9999);
        $order->setIncrementId(8888);

        $result = $instance->getRedirectUrl($order, 'paymenttoken');

        $this->assertStringContainsString('order_id=9999', $result);
        $this->assertStringContainsString('increment_id=8888', $result);
        $this->assertStringContainsString('payment_token=paymenttoken', $result);
        $this->assertStringContainsString('short_base_url=http://base_url.test/', $result);
        $this->assertStringContainsString('unsecure_base_url=http://unsecure_base_url.test/', $result);
        $this->assertStringContainsString('secure_base_url=https://secure_base_url.test/', $result);
    }

    /**
     * @magentoConfigFixture current_store payment/mollie_general/use_custom_redirect_url 1
     * @magentoConfigFixture current_store payment/mollie_general/custom_redirect_url https://www.mollie.com/?order_id={{order_id}}&payment_token={{payment_token}}&increment_id={{increment_id}}&short_base_url={{base_url}}&unsecure_base_url={{unsecure_base_url}}&secure_base_url={{secure_base_url}}
     */
    public function testTheVariablesAreCaseInsensitive(): void
    {
        $this->testAppendsTheParamsToTheUrl();
    }

    /**
     * @magentoConfigFixture current_store payment/mollie_general/use_custom_redirect_url 1
     * @magentoConfigFixture current_store payment/mollie_general/custom_redirect_url_parameters hashed_parameters
     * @magentoConfigFixture current_store payment/mollie_general/custom_redirect_url_hash dummyhashfortest
     * @magentoConfigFixture current_store payment/mollie_general/custom_redirect_url https://www.mollie.com/?hash={{ORDER_HASH}}
     */
    public function testHashesTheOrderId(): void
    {
        /** @var Encryptor $encryptor */
        $encryptor = $this->objectManager->get(Encryptor::class);

        /** @var Transaction $instance */
        $instance = $this->objectManager->create(Transaction::class);

        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);
        $order->setId(9999);

        $result = $instance->getRedirectUrl($order, 'paymenttoken');

        $query = parse_url($result, PHP_URL_QUERY);
        parse_str($query, $parts);
        $hash = base64_decode($parts['hash']);

        $this->assertEquals(9999, $encryptor->decrypt($hash));
    }

    /**
     * @magentoConfigFixture current_store payment/mollie_general/type live
     * @magentoConfigFixture current_store payment/mollie_general/use_webhooks enabled
     */
    public function testReturnsTheDefaultWebhookUrl(): void
    {
        /** @var Transaction $instance */
        $instance = $this->objectManager->create(Transaction::class);
        $result = $instance->getWebhookUrl([$this->objectManager->get(OrderInterface::class)]);

        $this->assertStringContainsString('mollie/checkout/webhook/', $result);
    }

    /**
     * @magentoConfigFixture current_store payment/mollie_general/type live
     * @magentoConfigFixture current_store payment/mollie_general/use_webhooks disabled
     */
    public function testIgnoresTheDisabledFlagWhenInLiveMode(): void
    {
        /** @var Transaction $instance */
        $instance = $this->objectManager->create(Transaction::class);
        $result = $instance->getWebhookUrl([$this->objectManager->get(OrderInterface::class)]);

        $this->assertStringContainsString('mollie/checkout/webhook/', $result);
    }

    /**
     * @magentoConfigFixture current_store payment/mollie_general/type test
     * @magentoConfigFixture current_store payment/mollie_general/use_webhooks disabled
     * @magentoConfigFixture current_store payment/mollie_general/custom_webhook_url custom_url_for_test
     */
    public function testReturnsNothingWhenDisabledAndInTestMode(): void
    {
        /** @var Transaction $instance */
        $instance = $this->objectManager->create(Transaction::class);
        $result = $instance->getWebhookUrl([$this->objectManager->get(OrderInterface::class)]);

        $this->assertEmpty($result);
    }

    /**
     * @magentoConfigFixture current_store payment/mollie_general/type test
     * @magentoConfigFixture current_store payment/mollie_general/use_webhooks custom_url
     * @magentoConfigFixture current_store payment/mollie_general/custom_webhook_url custom_url_for_test
     */
    public function testReturnsTheCustomWebhookUrl(): void
    {
        /** @var Transaction $instance */
        $instance = $this->objectManager->create(Transaction::class);
        $order = $this->objectManager->get(OrderInterface::class);
        $order->setEntityId(99999);
        $result = $instance->getWebhookUrl([$order]);

        $this->assertStringContainsString('custom_url_for_test?orderId[]=', $result);

        [, $encryptedId] = explode('orderId[]=', $result);

        $encryptedId = base64_decode($encryptedId);
        $id = $this->objectManager->get(EncryptorInterface::class)->decrypt($encryptedId);

        $this->assertEquals(99999, $id);
    }

    public function testAllowsToManuallySetAnUrl(): void
    {
        $order = $this->objectManager->create(OrderInterface::class);

        /** @var Transaction $instance */
        $instance = $this->objectManager->create(Transaction::class);

        $instance->setRedirectUrl('http://this-is-a-test.com/');

        $result = $instance->getRedirectUrl($order, 'PAYMENT_TOKEN_TEST');

        $this->assertStringContainsString('http://this-is-a-test.com/', $result);
    }
}
