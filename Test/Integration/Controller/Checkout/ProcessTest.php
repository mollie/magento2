<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Controller\Checkout;

use Exception;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Message\MessageInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\TestFramework\TestCase\AbstractController;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Service\Mollie\GetMollieStatusResult;
use Mollie\Payment\Service\Mollie\ProcessTransaction;
use Mollie\Payment\Service\Mollie\ValidateProcessRequest;
use Mollie\Payment\Test\Fakes\Service\Mollie\FakeValidateProcessRequest;
use Mollie\Payment\Test\Fakes\Service\Mollie\ProcessTransactionFake;

class ProcessTest extends AbstractController
{
    public function testDoesRedirectsToCartWhenNoIdProvided(): void
    {
        $this->dispatch('mollie/checkout/process');

        $this->assertRedirect($this->stringContains('checkout/cart'));
        $this->assertSessionMessages($this->equalTo(['Invalid return from Mollie.']), MessageInterface::TYPE_NOTICE);
    }

    public function testRedirectsToCartOnException(): void
    {
        $this->fakeValidation(['123' => 'abc']);

        $mollieModel = $this->createMock(Mollie::class);
        $mollieModel->method('processTransaction')->willThrowException(new Exception('[TEST] Transaction failed. Please verify your billing information and payment method, and try again.'));

        $this->_objectManager->addSharedInstance($mollieModel, Mollie::class);

        $this->dispatch('mollie/checkout/process?order_id=123');

        $this->assertRedirect($this->stringContains('checkout/cart'));
        $this->assertSessionMessages(
            $this->equalTo(['There was an error checking the transaction status.']),
            MessageInterface::TYPE_ERROR,
        );
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testUsesOrderIdParameter(): void
    {
        $order = $this->loadOrderById('100000001');
        $this->fakeValidation([(string) $order->getId() => 'abc']);

        $fake = $this->_objectManager->create(ProcessTransactionFake::class);
        $fake->setResponse($this->_objectManager->create(
            GetMollieStatusResult::class,
            ['status' => 'paid', 'method' => 'ideal'],
        ));

        $this->_objectManager->addSharedInstance($fake, ProcessTransaction::class);

        $this->dispatch('mollie/checkout/process?order_id=' . $order->getId());

        $this->assertEquals(1, $fake->getTimesCalled());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_list.php
     */
    public function testUsesOrderIdsParameter(): void
    {
        $order1 = $this->loadOrderById('100000001');
        $order2 = $this->loadOrderById('100000002');
        $order3 = $this->loadOrderById('100000003');
        $order4 = $this->loadOrderById('100000004');

        $this->fakeValidation([
            (string) $order1->getId() => 'abc',
            (string) $order2->getId() => 'def',
            (string) $order3->getId() => 'ghi',
            (string) $order4->getId() => 'jkl',
        ]);

        $fake = $this->_objectManager->create(ProcessTransactionFake::class);
        $fake->setResponse($this->_objectManager->create(
            GetMollieStatusResult::class,
            ['status' => 'paid', 'method' => 'ideal'],
        ));

        $this->_objectManager->addSharedInstance($fake, ProcessTransaction::class);

        $queryString = [
            'order_ids[]=' . $order1->getId(),
            'order_ids[]=' . $order2->getId(),
            'order_ids[]=' . $order3->getId(),
            'order_ids[]=' . $order4->getId(),
        ];

        $this->dispatch('mollie/checkout/process?' . implode('&', $queryString));

        $this->assertEquals(4, $fake->getTimesCalled());
    }

    /**
     * @param $orderId
     * @return OrderInterface
     */
    private function loadOrderById(string $orderId)
    {
        $repository = $this->_objectManager->get(OrderRepositoryInterface::class);
        $builder = $this->_objectManager->create(SearchCriteriaBuilder::class);
        $searchCriteria = $builder->addFilter('increment_id', $orderId, 'eq')->create();

        $orderList = $repository->getList($searchCriteria)->getItems();

        $order = array_shift($orderList);
        $order->setMollieTransactionId('ord_abc' . $orderId);
        $repository->save($order);

        return $order;
    }

    private function fakeValidation(array $response): void
    {
        $validateProcessRequest = $this->_objectManager->create(FakeValidateProcessRequest::class);
        $validateProcessRequest->setResponse($response);

        $this->_objectManager->addSharedInstance($validateProcessRequest, ValidateProcessRequest::class);
    }
}
