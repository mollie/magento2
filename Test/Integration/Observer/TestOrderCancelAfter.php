<?php

namespace Mollie\Payment\Observer;

use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class TestOrderCancelAfter extends IntegrationTestCase
{
    public function testDoesNothingWhenReordered(): void
    {
        $modelMock = $this->createMock(Mollie::class);
        $modelMock->expects($this->never())->method('cancelOrder');

        /** @var OrderCancelAfter $instance */
        $instance = $this->objectManager->create(OrderCancelAfter::class, [
            'mollieModel' => $modelMock,
        ]);

        $order = $this->objectManager->create(OrderInterface::class);
        $order->setReordered(true);

        $instance->execute($this->makeObserver($order));
    }

    public function testDoesNothingWhenNotPaidUsingOrdersApi(): void
    {
        $modelMock = $this->createMock(Mollie::class);
        $modelMock->expects($this->never())->method('cancelOrder');

        $helperMock = $this->createMock(\Mollie\Payment\Helper\General::class);
        $helperMock->method('isPaidUsingMollieOrdersApi')->willReturn(false);

        /** @var OrderCancelAfter $instance */
        $instance = $this->objectManager->create(OrderCancelAfter::class, [
            'mollieHelper' => $helperMock,
            'mollieModel' => $modelMock,
        ]);

        $order = $this->objectManager->create(OrderInterface::class);

        $instance->execute($this->makeObserver($order));
    }

    public function testCancelsTheOrder(): void
    {
        $modelMock = $this->createMock(Mollie::class);
        $modelMock->expects($this->once())->method('cancelOrder');

        $helperMock = $this->createMock(\Mollie\Payment\Helper\General::class);
        $helperMock->method('isPaidUsingMollieOrdersApi')->willReturn(true);

        /** @var OrderCancelAfter $instance */
        $instance = $this->objectManager->create(OrderCancelAfter::class, [
            'mollieHelper' => $helperMock,
            'mollieModel' => $modelMock,
        ]);

        $order = $this->objectManager->create(OrderInterface::class);

        $instance->execute($this->makeObserver($order));
    }

    public function testConvertsExceptionToErrorMessage(): void
    {
        $message = 'Error executing API call (422: Unprocessable Entity): The order cannot be canceled due to an open payment. Please wait until the payment is in a finalized state.. Documentation: https://docs.mollie.com/reference/v2/orders-api/cancel-order';
        $exception = new LocalizedException(__($message));
        $modelMock = $this->createMock(Mollie::class);
        $modelMock->method('cancelOrder')->willThrowException($exception);

        $helperMock = $this->createMock(\Mollie\Payment\Helper\General::class);
        $helperMock->method('isPaidUsingMollieOrdersApi')->willReturn(true);

        /** @var OrderCancelAfter $instance */
        $instance = $this->objectManager->create(OrderCancelAfter::class, [
            'mollieHelper' => $helperMock,
            'mollieModel' => $modelMock,
        ]);

        $order = $this->objectManager->create(OrderInterface::class);

        $instance->execute($this->makeObserver($order));

        /** @var \\Magento\Framework\Message\ManagerInterface $manager */
        $manager = $this->objectManager->get(\Magento\Framework\Message\ManagerInterface::class);

        $messages = $manager->getMessages();
        $this->assertCount(1, $messages->getErrors());
        $this->assertEquals($message, $messages->getErrors()[0]->getText());
    }

    private function makeObserver(OrderInterface $order): Observer
    {
        return new Observer([
            'event' => new Event([
                'order' => $order,
            ]),
        ]);
    }
}
