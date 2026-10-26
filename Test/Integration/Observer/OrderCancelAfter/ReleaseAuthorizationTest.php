<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Observer\OrderCancelAfter;

use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Mollie\Api\Fake\MockResponse;
use Mollie\Api\Http\Requests\ReleasePaymentAuthorizationRequest;
use Mollie\Payment\Observer\OrderCancelAfter\ReleaseAuthorization;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class ReleaseAuthorizationTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/capture_mode manual
     */
    public function testReleasesAuthorizationWhenNothingIsCaptured(): void
    {
        $fake = $this->loadFakeMollieApiClient();
        $fake->fake([ReleasePaymentAuthorizationRequest::class => MockResponse::noContent()], true);

        $this->execute($this->prepareOrder(0.0, 100.0));

        $fake->loadByStore()->assertSent(ReleasePaymentAuthorizationRequest::class);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/capture_mode manual
     */
    public function testReleasesRemainingAuthorizationWhenPartiallyInvoiced(): void
    {
        $fake = $this->loadFakeMollieApiClient();
        $fake->fake([ReleasePaymentAuthorizationRequest::class => MockResponse::noContent()], true);

        $this->execute($this->prepareOrder(44.0, 76.0));

        $fake->loadByStore()->assertSent(ReleasePaymentAuthorizationRequest::class);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/capture_mode manual
     */
    public function testDoesNotCallTheApiWhenFullyInvoiced(): void
    {
        $fake = $this->loadFakeMollieApiClient();
        $fake->fake([ReleasePaymentAuthorizationRequest::class => MockResponse::noContent()], true);

        $this->execute($this->prepareOrder(100.0, 100.0));

        $fake->loadByStore()->assertSentCount(0);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/capture_mode automatic
     */
    public function testDoesNotCallTheApiWhenCaptureIsAutomatic(): void
    {
        $fake = $this->loadFakeMollieApiClient();
        $fake->fake([ReleasePaymentAuthorizationRequest::class => MockResponse::noContent()], true);

        $this->execute($this->prepareOrder(0.0, 100.0));

        $fake->loadByStore()->assertSentCount(0);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/capture_mode manual
     */
    public function testAddsWarningAndDoesNotThrowWhenPaymentIsFinalized(): void
    {
        $fake = $this->loadFakeMollieApiClient();
        $fake->fake([
            ReleasePaymentAuthorizationRequest::class =>
                MockResponse::unprocessableEntity('Finalized payments cannot be reversed.'),
        ], true);

        $messageManager = $this->createMock(ManagerInterface::class);
        $messageManager->expects($this->once())->method('addWarningMessage');

        $instance = $this->objectManager->create(ReleaseAuthorization::class, [
            'messageManager' => $messageManager,
        ]);

        $instance->execute($this->observerFor($this->prepareOrder(44.0, 76.0)));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_list.php
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/capture_mode manual
     */
    public function testKeepsTheAuthorizationWhenAnotherOrderOfThePaymentStillHasToBeCaptured(): void
    {
        $fake = $this->loadFakeMollieApiClient();
        $fake->fake([ReleasePaymentAuthorizationRequest::class => MockResponse::noContent()], true);
        [$canceledOrder] = $this->prepareSharedOrders();

        $this->execute($canceledOrder);

        $fake->loadByStore()->assertSentCount(0);
        $this->assertStringContainsString('was not released', $this->allComments($canceledOrder));
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_list.php
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/capture_mode manual
     */
    public function testReleasesTheAuthorizationWhenTheOtherOrdersOfThePaymentAreCanceled(): void
    {
        $fake = $this->loadFakeMollieApiClient();
        $fake->fake([ReleasePaymentAuthorizationRequest::class => MockResponse::noContent()], true);
        [$canceledOrder, $otherOrder] = $this->prepareSharedOrders();
        $otherOrder->setState(Order::STATE_CANCELED);
        $this->objectManager->get(OrderRepositoryInterface::class)->save($otherOrder);

        $this->execute($canceledOrder);

        $fake->loadByStore()->assertSent(ReleasePaymentAuthorizationRequest::class);
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order_list.php
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/capture_mode manual
     */
    public function testReleasesTheAuthorizationWhenTheOtherOrdersOfThePaymentAreFullyInvoiced(): void
    {
        $fake = $this->loadFakeMollieApiClient();
        $fake->fake([ReleasePaymentAuthorizationRequest::class => MockResponse::noContent()], true);
        [$canceledOrder, $otherOrder] = $this->prepareSharedOrders();
        $otherOrder->setBaseTotalInvoiced($otherOrder->getBaseGrandTotal());
        $this->objectManager->get(OrderRepositoryInterface::class)->save($otherOrder);

        $this->execute($canceledOrder);

        $fake->loadByStore()->assertSent(ReleasePaymentAuthorizationRequest::class);
    }

    /**
     * @return OrderInterface[]
     */
    private function prepareSharedOrders(): array
    {
        $repository = $this->objectManager->get(OrderRepositoryInterface::class);

        return array_map(function (string $incrementId) use ($repository): OrderInterface {
            $order = $this->loadOrderById($incrementId);
            $order->getPayment()->setMethod('mollie_methods_creditcard');
            $order->setMollieTransactionId('tr_TestSharedTransaction');
            $order->setBaseTotalInvoiced(0.0);

            return $repository->save($order);
        }, ['100000002', '100000004']);
    }

    private function allComments(OrderInterface $order): string
    {
        return implode(' ', array_map(
            fn ($history): string => (string) $history->getComment(),
            $order->getStatusHistories() ?? [],
        ));
    }

    private function prepareOrder(float $invoiced, float $grandTotal): OrderInterface
    {
        $order = $this->loadOrderById('100000001');
        $order->getPayment()->setMethod('mollie_methods_creditcard');
        $order->setMollieTransactionId('tr_dummytransaction');
        $order->setBaseTotalInvoiced($invoiced);
        $order->setBaseGrandTotal($grandTotal);

        return $order;
    }

    private function execute(OrderInterface $order): void
    {
        $instance = $this->objectManager->create(ReleaseAuthorization::class);
        $instance->execute($this->observerFor($order));
    }

    private function observerFor(OrderInterface $order): Observer
    {
        $event = new Event(['order' => $order]);

        return new Observer(['event' => $event]);
    }
}
