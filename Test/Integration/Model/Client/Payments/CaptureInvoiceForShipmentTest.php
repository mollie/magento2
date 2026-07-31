<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Model\Client\Payments;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\ShipmentInterface;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Model\Order\ShipmentFactory;
use Mollie\Api\Fake\MockResponse;
use Mollie\Api\Http\Requests\CreatePaymentCaptureRequest;
use Mollie\Payment\Exceptions\PartialCaptureNotSupported;
use Mollie\Payment\Model\Client\Payments\CaptureInvoiceForShipment;
use Mollie\Payment\Test\Fakes\Service\Mollie\FakeMollieApiClient;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class CaptureInvoiceForShipmentTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testRefusesAPartialShipmentWhenTheMethodDoesNotSupportPartialCaptures(): void
    {
        $this->fakeCaptureApi();

        $order = $this->prepareOrder('mollie_methods_riverty');
        $instance = $this->objectManager->create(CaptureInvoiceForShipment::class);

        $this->expectException(PartialCaptureNotSupported::class);
        $instance->execute($this->shipmentFor($order, 1));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testCreatesNoInvoiceAndCallsNoApiForARefusedPartialShipment(): void
    {
        $fake = $this->fakeCaptureApi();

        $order = $this->prepareOrder('mollie_methods_riverty');
        $instance = $this->objectManager->create(CaptureInvoiceForShipment::class);

        try {
            $instance->execute($this->shipmentFor($order, 1));
            $this->fail('Expected the partial shipment to be refused');
        } catch (PartialCaptureNotSupported $exception) {
            $this->assertSame(0, $this->countStoredInvoices($order));
            $fake->loadByStore()->assertSentCount(0);
        }
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testCapturesAShipmentThatCoversTheCompleteOrder(): void
    {
        $fake = $this->fakeCaptureApi();

        $order = $this->prepareOrder('mollie_methods_riverty');
        $instance = $this->objectManager->create(CaptureInvoiceForShipment::class);

        $invoice = $instance->execute($this->shipmentFor($order, 2));

        $this->assertNotNull($invoice);
        $fake->loadByStore()->assertSent(CreatePaymentCaptureRequest::class);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testCapturesAPartialShipmentWhenTheMethodSupportsPartialCaptures(): void
    {
        $fake = $this->fakeCaptureApi();

        $order = $this->prepareOrder('mollie_methods_creditcard');
        $instance = $this->objectManager->create(CaptureInvoiceForShipment::class);

        $invoice = $instance->execute($this->shipmentFor($order, 1));

        $this->assertNotNull($invoice);
        $fake->loadByStore()->assertSent(CreatePaymentCaptureRequest::class);
    }

    private function fakeCaptureApi(): FakeMollieApiClient
    {
        $fake = $this->loadFakeMollieApiClient();
        $fake->fake([
            CreatePaymentCaptureRequest::class => MockResponse::created(
                '{"resource":"capture","id":"cpt_dummycapture","paymentId":"tr_dummytransaction","status":"pending"}',
            ),
        ], true);

        return $fake;
    }

    private function prepareOrder(string $method): Order
    {
        /** @var Order $order */
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod($method);
        $order->setMollieTransactionId('tr_dummytransaction');

        $total = $this->totalOfAllItems($order);
        $order->setBaseSubtotal($total);
        $order->setSubtotal($total);
        $order->setBaseGrandTotal($total);
        $order->setGrandTotal($total);

        return $order;
    }

    private function totalOfAllItems(Order $order): float
    {
        return array_reduce(
            $order->getAllItems(),
            function (float $total, OrderItem $item): float {
                $rowTotal = (float) $item->getBasePrice() * (float) $item->getQtyOrdered();
                $item->setBaseRowTotal($rowTotal);
                $item->setRowTotal($rowTotal);

                return $total + $rowTotal;
            },
            0.0,
        );
    }

    private function shipmentFor(Order $order, float $qty): ShipmentInterface
    {
        $items = array_reduce(
            $order->getAllItems(),
            fn (array $items, $item): array => $items + [(int) $item->getId() => $qty],
            [],
        );

        return $this->objectManager->create(ShipmentFactory::class)->create($order, $items);
    }

    private function countStoredInvoices(Order $order): int
    {
        $searchCriteria = $this->objectManager->create(SearchCriteriaBuilder::class)
            ->addFilter('order_id', $order->getEntityId(), 'eq')
            ->create();

        return $this->objectManager->get(InvoiceRepositoryInterface::class)->getList($searchCriteria)->getTotalCount();
    }
}
