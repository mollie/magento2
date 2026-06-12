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
