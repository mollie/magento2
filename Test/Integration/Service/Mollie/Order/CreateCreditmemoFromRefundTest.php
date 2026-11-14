<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie\Order;

use Magento\Backend\Model\Session;
use Magento\CatalogInventory\Api\StockConfigurationInterface;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Framework\Registry;
use Magento\Sales\Api\CreditmemoRepositoryInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Controller\Adminhtml\Order\CreditmemoLoader;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\CreditmemoFactory;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Service\CreditmemoService;
use Mollie\Payment\Service\Mollie\Order\CreateCreditmemoFromRefund;
use Mollie\Payment\Test\Fakes\Api\Resources\PaymentWithRefundsFake;
use PHPUnit\Framework\TestCase;

class CreateCreditmemoFromRefundTest extends TestCase
{
    public function testCreatesACreditmemoForEachRefundInTheSameRequest(): void
    {
        $registry = new Registry();

        $passedPayment = $this->createMock(Payment::class);
        $passedPayment->expects($this->never())->method('setTransactionId');
        $passedPayment->expects($this->never())->method('setLastTransId');

        $order = $this->createStub(Order::class);
        $order->method('getId')->willReturn(1);
        $order->method('getPayment')->willReturn($passedPayment);
        $order->method('getAllItems')->willReturn([]);

        $transactionIds = [];
        $lastTransIds = [];
        $refundedPayment = $this->createMock(Payment::class);
        $refundedPayment->expects($this->exactly(2))
            ->method('setTransactionId')
            ->willReturnCallback(function (string $id) use (&$transactionIds, &$refundedPayment): Payment {
                $transactionIds[] = $id;

                return $refundedPayment;
            });
        $refundedPayment->expects($this->exactly(2))
            ->method('setLastTransId')
            ->willReturnCallback(function (string $id) use (&$lastTransIds, &$refundedPayment): Payment {
                $lastTransIds[] = $id;

                return $refundedPayment;
            });

        $loadedOrder = $this->createStub(Order::class);
        $loadedOrder->method('load')->willReturnSelf();
        $loadedOrder->method('getId')->willReturn(1);
        $loadedOrder->method('canCreditmemo')->willReturn(true);
        $loadedOrder->method('getPayment')->willReturn($refundedPayment);

        $orderFactory = $this->createStub(OrderFactory::class);
        $orderFactory->method('create')->willReturn($loadedOrder);

        $invoice = $this->createStub(Invoice::class);
        $invoice->method('getId')->willReturn(1);

        $invoiceRepository = $this->createStub(InvoiceRepositoryInterface::class);
        $invoiceRepository->method('get')->willReturn($invoice);

        $creditmemoFactory = $this->createStub(CreditmemoFactory::class);
        $creditmemoFactory->method('createByInvoice')->willReturnCallback(
            function (Invoice $invoice, array $data) use ($loadedOrder): Creditmemo {
                $creditmemo = $this->getMockBuilder(Creditmemo::class)
                    ->disableOriginalConstructor()
                    ->onlyMethods(['getAllItems', 'addComment', 'getOrder'])
                    ->getMock();
                $creditmemo->method('getOrder')->willReturn($loadedOrder);
                $creditmemo->method('getAllItems')->willReturn([]);
                $creditmemo->expects($this->once())->method('addComment');
                $creditmemo->setData('adjustment_positive', $data['adjustment_positive']);

                return $creditmemo;
            }
        );

        $creditmemoLoader = new CreditmemoLoader(
            $this->createStub(CreditmemoRepositoryInterface::class),
            $creditmemoFactory,
            $orderFactory,
            $invoiceRepository,
            $this->createStub(EventManagerInterface::class),
            $this->createStub(Session::class),
            $this->createStub(MessageManagerInterface::class),
            $registry,
            $this->createStub(StockConfigurationInterface::class),
        );

        $refundedCreditmemos = [];
        $creditmemoService = $this->createMock(CreditmemoService::class);
        $creditmemoService->expects($this->exactly(2))
            ->method('refund')
            ->willReturnCallback(function (Creditmemo $creditmemo) use (&$refundedCreditmemos): Creditmemo {
                $refundedCreditmemos[] = $creditmemo;

                return $creditmemo;
            });

        $instance = new CreateCreditmemoFromRefund($creditmemoLoader, $creditmemoService, $registry);

        $refunds = (new PaymentWithRefundsFake())
            ->withRefund('re_external_123', 10)
            ->withRefund('re_external_456', 5)
            ->refunds();

        foreach ($refunds as $refund) {
            $instance->execute($order, $invoice, $refund);
        }

        $this->assertCount(2, $refundedCreditmemos);
        $this->assertNotSame($refundedCreditmemos[0], $refundedCreditmemos[1]);
        $this->assertSame('10.00', $refundedCreditmemos[0]->getData('adjustment_positive'));
        $this->assertSame('5.00', $refundedCreditmemos[1]->getData('adjustment_positive'));
        $this->assertSame('re_external_123', $refundedCreditmemos[0]->getMollieTransactionId());
        $this->assertSame('re_external_456', $refundedCreditmemos[1]->getMollieTransactionId());
        $this->assertSame(['re_external_123', 're_external_456'], $transactionIds);
        $this->assertSame(['re_external_123', 're_external_456'], $lastTransIds);
    }
}
