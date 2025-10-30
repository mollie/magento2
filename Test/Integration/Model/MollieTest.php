<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Model;

use Exception;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Api\Fake\MockResponse;
use Mollie\Api\Http\Requests\CreatePaymentRefundRequest;
use Mollie\Api\Http\Requests\CreatePaymentRequest;
use Mollie\Api\Http\Requests\GetPaymentRequest;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Model\Client\Payments;
use Mollie\Payment\Model\Methods\Ideal;
use Mollie\Payment\Model\Methods\Pointofsale;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Service\Mollie\StartTransaction;
use Mollie\Payment\Test\Fakes\Model\Client\Payments\ProcessTransactionFake as PaymentsProcessTransactionFake;
use Mollie\Payment\Test\Fakes\Service\Mollie\FakeMollieApiClient;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class MollieTest extends IntegrationTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The OrderLockService interferes with the tests, so we replace it with a fake.
        $this->loadFakeOrderLockService();
    }

    public function processTransactionUsesTheCorrectApiProvider(): array
    {
        return [
            'payments' => ['tr_abcdefg', 'payments'],
        ];
    }

    /**
     * @dataProvider processTransactionUsesTheCorrectApiProvider
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     * @magentoConfigFixture default_store payment/mollie_general/type test
     */
    public function testProcessTransactionUsesTheCorrectApi(string $orderId, string $type): void
    {
        $order = $this->loadOrder('100000001');
        $order->setMollieTransactionId($orderId);
        $this->objectManager->get(OrderRepositoryInterface::class)->save($order);

        $mollieHelperMock = $this->createMock(General::class);
        $mollieHelperMock->method('getApiKey')->willReturn('test_TEST_API_KEY_THAT_IS_LONG_ENOUGH');

        $paymentsApiMock = $this->createMock(Payments::class);
        $paymentsProcessTransactionFake = $this->objectManager->create(PaymentsProcessTransactionFake::class);
        $paymentsProcessTransactionFake->disableParentCall();

        $mollieApiMock = $this->createMock(\Mollie\Payment\Service\Mollie\MollieApiClient::class);
        $mollieApiMock->method('loadByStore')->willReturn(new MollieApiClient());

        /** @var Mollie $instance */
        $instance = $this->objectManager->create(Mollie::class, [
            'paymentsApi' => $paymentsApiMock,
            'mollieHelper' => $mollieHelperMock,
            'paymentsProcessTransaction' => $paymentsProcessTransactionFake,
            'mollieApiClient' => $mollieApiMock,
        ]);

        $instance->processTransaction($order->getEntityId());
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/method payment
     * @magentoConfigFixture default_store payment/mollie_general/mode test
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     */
    public function testStartTransactionWithMethodPayment(): void
    {
        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);
        $order->setEntityId(1);
        $payment = $this->objectManager->create(OrderPaymentInterface::class);
        $payment->setMethod('mollie_methods_ideal');
        $order->setPayment($payment);

        $paymentsApiMock = $this->createMock(Payments::class);
        $paymentsApiMock->method('startTransaction')->willReturn('payment');

        $apiClient = $this->objectManager->create(FakeMollieApiClient::class);
        $apiClient->setInstance(new MollieApiClient());

        /** @var StartTransaction $instance */
        $instance = $this->objectManager->create(StartTransaction::class, [
            'paymentsApi' => $paymentsApiMock,
            'mollieApiClient' => $apiClient,
        ]);

        $result = $instance->execute($order);

        $this->assertEquals('payment', $result);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/method order
     * @magentoConfigFixture default_store payment/mollie_general/mode test
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     */
    public function testRetriesOnACurlTimeout(): void
    {
        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);
        $order->setEntityId(1);
        $payment = $this->objectManager->create(OrderPaymentInterface::class);
        $payment->setMethod('mollie_methods_ideal');
        $order->setPayment($payment);

        $client = MollieApiClient::fake([
            CreatePaymentRequest::class => MockResponse::ok(),
        ]);

        $paymentsApiMock = $this->createMock(Payments::class);
        $paymentsApiMock->method('startTransaction')->willReturnOnConsecutiveCalls(
            $this->throwException(new Exception('cURL error 28')),
            'payment',
        );

        $apiClient = $this->objectManager->create(FakeMollieApiClient::class);
        $apiClient->setInstance($client);

        /** @var StartTransaction $instance */
        $instance = $this->objectManager->create(StartTransaction::class, [
            'paymentsApi' => $paymentsApiMock,
            'mollieApiClient' => $apiClient,
        ]);

        $result = $instance->execute($order);

        $this->assertEquals('payment', $result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @throws LocalizedException
     */
    public function testAssignsIssuerId(): void
    {
        $data = new DataObject();
        $data->setAdditionalData(['selected_issuer' => 'TESTBANK']);

        $order = $this->loadOrder('100000001');
        $payment = $order->getPayment();

        /** @var Mollie $instance */
        $instance = $this->objectManager->create(Ideal::class);
        $instance->setInfoInstance($payment);
        $instance->assignData($data);

        $this->assertEquals('TESTBANK', $payment->getAdditionalInformation()['selected_issuer']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @throws LocalizedException
     */
    public function testAssignsTerminalId(): void
    {
        $data = new DataObject();
        $data->setAdditionalData(['selected_terminal' => 'term_randomstringid']);

        $order = $this->loadOrder('100000001');
        $payment = $order->getPayment();

        /** @var Mollie $instance */
        $instance = $this->objectManager->create(Pointofsale::class);
        $instance->setInfoInstance($payment);
        $instance->assignData($data);

        $this->assertEquals('term_randomstringid', $payment->getAdditionalInformation()['selected_terminal']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @throws LocalizedException
     */
    public function testAssignsCardToken(): void
    {
        $data = new DataObject();
        $data->setAdditionalData(['card_token' => 'abc123']);

        $order = $this->loadOrder('100000001');
        $payment = $order->getPayment();

        /** @var Mollie $instance */
        $instance = $this->objectManager->create(Ideal::class);
        $instance->setInfoInstance($payment);
        $instance->assignData($data);

        $this->assertEquals('abc123', $payment->getAdditionalInformation()['card_token']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_general/currency 0
     * @magentoConfigFixture default_store payment/mollie_general/type test
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     *
     * @return void
     * @throws LocalizedException
     */
    public function testRefundsInTheCorrectAmount(): void
    {
        if (getenv('CI')) {
            $this->markTestSkipped('Fails on CI');
        }

        $order = $this->loadOrder('100000001');
        $order->setMollieTransactionId('tr_12345');

        $client = MollieApiClient::fake([
            GetPaymentRequest::class => MockResponse::ok('payment'),
            CreatePaymentRefundRequest::class => MockResponse::ok('refund'),
        ]);

        $paymentMock = $this->createMock(Payment::class);
        $paymentMock->method('refund')->with($this->callback(function (array $parameters): bool {
            $this->assertEquals(56.78, $parameters['amount']['value']);

            return true;
        }));

        $mollieApiMock = $this->objectManager->create(FakeMollieApiClient::class);
        $mollieApiMock->setInstance($client);

        $this->objectManager->addSharedInstance($mollieApiMock, \Mollie\Payment\Service\Mollie\MollieApiClient::class);

        /** @var Mollie $instance */
        $instance = $this->objectManager->create(Mollie::class);

        /** @var $infoPayment $infoPayment */
        $infoPayment = $this->objectManager->get(\Magento\Sales\Model\Order\Payment::class);
        $infoPayment->setOrder($order);

        $creditmemo = $this->objectManager->create(CreditmemoInterface::class);
        $creditmemo->setBaseGrandTotal(12.34);
        $creditmemo->setGrandTotal(56.78);
        $infoPayment->setCreditmemo($creditmemo);

        $instance->refund($infoPayment, 12.34);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_general/enabled 1
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/active 1
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     * @magentoConfigFixture default_store payment/mollie_general/type test
     */
    public function testIsAvailableForValidStreetnames(): void
    {
        $this->loadFakeEncryptor()->addReturnValue(
            'test_dummyapikeywhichmustbe30characterslong',
            'test_dummyapikeywhichmustbe30characterslong',
        );

        /** @var Ideal $instance */
        $instance = $this->objectManager->create(Ideal::class);

        $quote = $this->objectManager->create(Quote::class);
        $quote->getShippingAddress()->setStreetFull(str_repeat('tenletters', 10));

        $this->assertTrue($instance->isAvailable($quote));
    }
}
