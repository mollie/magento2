<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Model;

use Magento\Framework\DataObject;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Api\Endpoints\MethodEndpoint;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Model\Client\Orders;
use Mollie\Payment\Model\Client\Payments;
use Mollie\Payment\Model\Methods\Ideal;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Test\Fakes\Model\Client\Orders\ProcessTransactionFake;
use Mollie\Payment\Test\Fakes\Service\Mollie\FakeMollieApiClient;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class MollieTest extends IntegrationTestCase
{
    protected function setUpWithoutVoid()
    {
        // The OrderLockService interferes with the tests, so we replace it with a fake.
        $this->loadFakeOrderLockService();
    }

    public function processTransactionUsesTheCorrectApiProvider()
    {
        return [
            'orders' => ['ord_abcdefg', 'orders'],
            'payments' => ['tr_abcdefg', 'payments'],
        ];
    }

    /**
     * @dataProvider processTransactionUsesTheCorrectApiProvider
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     * @magentoConfigFixture default_store payment/mollie_general/type test
     */
    public function testProcessTransactionUsesTheCorrectApi($orderId, $type)
    {
        $order = $this->loadOrder('100000001');
        $order->setMollieTransactionId($orderId);
        $this->objectManager->get(OrderRepositoryInterface::class)->save($order);

        $mollieHelperMock = $this->createMock(General::class);
        $mollieHelperMock->method('getApiKey')->willReturn('test_TEST_API_KEY_THAT_IS_LONG_ENOUGH');

        $paymentsApiMock = $this->createMock(Payments::class);
        $orderProcessTransactionFake = $this->objectManager->create(ProcessTransactionFake::class);

        $mollieApiMock = $this->createMock(\Mollie\Payment\Service\Mollie\MollieApiClient::class);
        $mollieApiMock->method('loadByStore')->willReturn(new \Mollie\Api\MollieApiClient);

        if ($type == 'orders') {
            $orderProcessTransactionFake->disableParentCall();
        }

        if ($type == 'payments') {
            $paymentsApiMock->expects($this->once())->method('processTransaction')->willReturn(['success' => true]);
        }

        /** @var Mollie $instance */
        $instance = $this->objectManager->create(Mollie::class, [
            'paymentsApi' => $paymentsApiMock,
            'mollieHelper' => $mollieHelperMock,
            'ordersProcessTransaction' => $orderProcessTransactionFake,
            'mollieApiClient' => $mollieApiMock,
        ]);

        $instance->processTransaction($order->getEntityId());

        if ($type == 'orders') {
            $this->assertEquals(1, $orderProcessTransactionFake->getTimesCalled());
        }
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/method order
     * @magentoConfigFixture default_store payment/mollie_general/mode test
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     */
    public function testStartTransactionWithMethodOrder()
    {
        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);
        $order->setEntityId(1);
        $payment = $this->objectManager->create(OrderPaymentInterface::class);
        $payment->setMethod('mollie_methods_ideal');
        $order->setPayment($payment);

        $ordersApiMock = $this->createMock(Orders::class);
        $ordersApiMock->method('startTransaction')->willReturn('order');

        $paymentsApiMock = $this->createMock(Payments::class);
        $paymentsApiMock->expects($this->never())->method('startTransaction');

        /** @var Mollie $instance */
        $instance = $this->objectManager->create(Mollie::class, [
            'ordersApi' => $ordersApiMock,
            'paymentsApi' => $paymentsApiMock,
        ]);

        $result = $instance->startTransaction($order);

        $this->assertEquals('order', $result);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/method payment
     * @magentoConfigFixture default_store payment/mollie_general/mode test
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     */
    public function testStartTransactionWithMethodPayment()
    {
        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);
        $order->setEntityId(1);
        $payment = $this->objectManager->create(OrderPaymentInterface::class);
        $payment->setMethod('mollie_methods_ideal');
        $order->setPayment($payment);

        $ordersApiMock = $this->createMock(Orders::class);
        $ordersApiMock->expects($this->never())->method('startTransaction');

        $paymentsApiMock = $this->createMock(Payments::class);
        $paymentsApiMock->method('startTransaction')->willReturn('payment');

        /** @var Mollie $instance */
        $instance = $this->objectManager->create(Mollie::class, [
            'ordersApi' => $ordersApiMock,
            'paymentsApi' => $paymentsApiMock,
        ]);

        $result = $instance->startTransaction($order);

        $this->assertEquals('payment', $result);
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_methods_ideal/method order
     * @magentoConfigFixture default_store payment/mollie_general/mode test
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     */
    public function testRetriesOnACurlTimeout()
    {
        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);
        $order->setEntityId(1);
        $payment = $this->objectManager->create(OrderPaymentInterface::class);
        $payment->setMethod('mollie_methods_ideal');
        $order->setPayment($payment);

        $ordersApiMock = $this->createMock(Orders::class);
        $ordersApiMock->expects($this->exactly(3))->method('startTransaction')->willThrowException(
            new ApiException(
                'cURL error 28: Connection timed out after 10074 milliseconds ' .
                '(see http://curl.haxx.se/libcurl/c/libcurl-errors.html)'
            )
        );

        $paymentsApiMock = $this->createMock(Payments::class);
        $paymentsApiMock->expects($this->once())->method('startTransaction')->willReturn('payment');

        /** @var Mollie $instance */
        $instance = $this->objectManager->create(Mollie::class, [
            'ordersApi' => $ordersApiMock,
            'paymentsApi' => $paymentsApiMock,
        ]);

        $result = $instance->startTransaction($order);

        $this->assertEquals('payment', $result);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testAssignsIssuerId()
    {
        $data = new DataObject;
        $data->setAdditionalData(['selected_issuer' => 'TESTBANK']);

        $order = $this->loadOrder('100000001');
        $payment = $order->getPayment();

        /** @var Mollie $instance */
        $instance = $this->objectManager->create(\Mollie\Payment\Model\Methods\Ideal::class);
        $instance->setInfoInstance($payment);
        $instance->assignData($data);

        $this->assertEquals('TESTBANK', $payment->getAdditionalInformation()['selected_issuer']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testAssignsTerminalId()
    {
        $data = new DataObject;
        $data->setAdditionalData(['selected_terminal' => 'term_randomstringid']);

        $order = $this->loadOrder('100000001');
        $payment = $order->getPayment();

        /** @var Mollie $instance */
        $instance = $this->objectManager->create(\Mollie\Payment\Model\Methods\Pointofsale::class);
        $instance->setInfoInstance($payment);
        $instance->assignData($data);

        $this->assertEquals('term_randomstringid', $payment->getAdditionalInformation()['selected_terminal']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function testAssignsCardToken()
    {
        $data = new DataObject;
        $data->setAdditionalData(['card_token' => 'abc123']);

        $order = $this->loadOrder('100000001');
        $payment = $order->getPayment();

        /** @var Mollie $instance */
        $instance = $this->objectManager->create(\Mollie\Payment\Model\Methods\Ideal::class);
        $instance->setInfoInstance($payment);
        $instance->assignData($data);

        $this->assertEquals('abc123', $payment->getAdditionalInformation()['card_token']);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_dummyapikeywhichmustbe30characterslong
     */
    public function testDoesNotFallbackOnPaymentsApiForSpecificMethods()
    {
        $this->expectException(LocalizedException::class);

        $encryptorMock = $this->createMock(EncryptorInterface::class);
        $encryptorMock->method('decrypt')->willReturn('test_dummyapikeywhichmustbe30characterslong');

        $mollieHelper = $this->objectManager->create(General::class, ['encryptor' => $encryptorMock]);

        $order = $this->loadOrder('100000001');
        $order->getPayment()->setMethod('mollie_methods_voucher');

        $ordersApi = $this->createMock(Orders::class);
        $ordersApi->expects($this->once())->method('startTransaction')->willThrowException(
            new \Exception('[test] Error while starting transaction')
        );

        $paymentsApi = $this->createMock(Payments::class);
        $paymentsApi->expects($this->never())->method('startTransaction');

        /** @var Mollie $instance */
        $instance = $this->objectManager->create(Mollie::class, [
            'ordersApi' => $ordersApi,
            'paymentsApi' => $paymentsApi,
            'mollieHelper' => $mollieHelper,
        ]);

        $instance->startTransaction($order);
    }

    public function testGetIssuersHasAnSequentialIndex()
    {
        $response = new \stdClass();
        $response->issuers = [
            ['id' => 'ZZissuer', 'name' => 'ZZissuer'],
            ['id' => 'AAissuer', 'name' => 'AAissuer'],
        ];

        $methodEndpointMock = $this->createMock(MethodEndpoint::class);
        $methodEndpointMock->method('get')->willReturn($response);

        $mollieApi = new MollieApiClient;
        $mollieApi->methods = $methodEndpointMock;

        /** @var Mollie $instance */
        $instance = $this->objectManager->create(Mollie::class);

        $result = $instance->getIssuers($mollieApi, 'mollie_methods_ideal', 'radio');

        $this->assertSame(array_values($result), $result);
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

        $paymentMock = $this->createMock(Payment::class);
        $paymentMock->method('refund')->with($this->callback(function ($parameters) {
            $this->assertEquals(56.78, $parameters['amount']['value']);

            return true;
        }));

        $client = $this->loadFakeMollieApiClient();
        $client->returnFakePayment($paymentMock);

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
            'test_dummyapikeywhichmustbe30characterslong'
        );

        /** @var Ideal $instance */
        $instance = $this->objectManager->create(Ideal::class);

        $quote = $this->objectManager->create(Quote::class);
        $quote->getShippingAddress()->setStreetFull(str_repeat('tenletters', 10));

        $this->assertTrue($instance->isAvailable($quote));
    }
}
