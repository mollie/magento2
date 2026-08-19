<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Observer\SalesOrderShipmentSaveBefore;

use Magento\Framework\Event;
use Magento\Framework\Event\Observer;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\Order\Shipment;
use Mollie\Payment\Model\Client\Payments\CaptureInvoiceForShipment;
use Mollie\Payment\Observer\SalesOrderShipmentSaveBefore\CaptureShipment;
use Mollie\Payment\Service\LockService;
use Mollie\Payment\Test\Fakes\Framework\Lock\LockManagerFake;
use Mollie\Payment\Test\Fakes\Model\Client\Payments\CaptureInvoiceForShipmentFake;
use Mollie\Payment\Test\Fakes\Service\LockServiceFake;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use RuntimeException;

class CaptureShipmentTest extends IntegrationTestCase
{
    private const KEY = 'mollie.order.999999';

    /**
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/capture_mode manual
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/when_to_capture shipment
     */
    public function testHoldsTheLockWhileTheOrderIsCaptured(): void
    {
        $lockService = $this->createLockService(new LockManagerFake());
        $capture = $this->createCaptureFake();

        $lockedDuringCapture = null;
        $capture->givenThisRunsDuringTheCapture(function () use ($lockService, &$lockedDuringCapture): void {
            $lockedDuringCapture = $lockService->isLocked(static::KEY);
        });

        $this->createObserver($lockService, $capture)->execute($this->buildObserver());

        $this->assertSame(1, $capture->getTimesCalled());
        $this->assertTrue($lockedDuringCapture);
        $this->assertFalse($lockService->isLocked(static::KEY));
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/capture_mode manual
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/when_to_capture shipment
     */
    public function testReleasesTheLockWhenTheCaptureFails(): void
    {
        $lockManager = new LockManagerFake();
        $lockService = $this->createLockService($lockManager);
        $capture = $this->createCaptureFake();
        $capture->givenTheCaptureFailsWith(new RuntimeException('Capture failed'));

        try {
            $this->createObserver($lockService, $capture)->execute($this->buildObserver());
            $this->fail('Expected the capture to fail');
        } catch (RuntimeException $exception) {
            $this->assertSame('Capture failed', $exception->getMessage());
        }

        $this->assertFalse($lockService->isLocked(static::KEY));
        $this->assertSame(1, $lockManager->unlockCallCount(static::KEY));
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/capture_mode manual
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/when_to_capture shipment
     */
    public function testDoesNotCaptureWhenAnotherProcessHoldsTheLock(): void
    {
        $lockService = $this->objectManager->create(
            LockServiceFake::class,
            ['lockManager' => new LockManagerFake()]
        );
        $lockService->givenTheLockIsHeldByAnotherProcess();

        $capture = $this->createCaptureFake();

        try {
            $this->createObserver($lockService, $capture)->execute($this->buildObserver());
            $this->fail('Expected a LocalizedException');
        } catch (LocalizedException $exception) {
            $this->assertSame('Unable to get lock for ' . static::KEY, $exception->getMessage());
        }

        $this->assertSame(0, $capture->getTimesCalled());
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/capture_mode automatic
     */
    public function testTakesNoLockWhenManualCaptureIsNotUsed(): void
    {
        $lockManager = new LockManagerFake();
        $capture = $this->createCaptureFake();

        $this->createObserver($this->createLockService($lockManager), $capture)->execute($this->buildObserver());

        $this->assertSame(0, $capture->getTimesCalled());
        $this->assertSame(0, $lockManager->lockCallCount(static::KEY));
    }

    /**
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/capture_mode manual
     * @magentoConfigFixture default_store payment/mollie_methods_creditcard/when_to_capture invoice
     */
    public function testTakesNoLockWhenTheCaptureHappensOnInvoice(): void
    {
        $lockManager = new LockManagerFake();
        $capture = $this->createCaptureFake();

        $this->createObserver($this->createLockService($lockManager), $capture)->execute($this->buildObserver());

        $this->assertSame(0, $capture->getTimesCalled());
        $this->assertSame(0, $lockManager->lockCallCount(static::KEY));
    }

    private function buildObserver(): Observer
    {
        /** @var Payment $payment */
        $payment = $this->objectManager->create(Payment::class);
        $payment->setMethod('mollie_methods_creditcard');

        /** @var Order $order */
        $order = $this->objectManager->create(Order::class);
        $order->setEntityId(999999);
        $order->setPayment($payment);

        /** @var Shipment $shipment */
        $shipment = $this->objectManager->create(Shipment::class);
        $shipment->setOrder($order);

        /** @var Event $event */
        $event = $this->objectManager->create(Event::class);
        $event->setData('shipment', $shipment);

        /** @var Observer $observer */
        $observer = $this->objectManager->create(Observer::class);
        $observer->setEvent($event);

        return $observer;
    }

    private function createLockService(LockManagerFake $lockManager): LockService
    {
        return $this->objectManager->create(LockService::class, ['lockManager' => $lockManager]);
    }

    private function createCaptureFake(): CaptureInvoiceForShipmentFake
    {
        return $this->objectManager->create(CaptureInvoiceForShipmentFake::class);
    }

    private function createObserver(
        LockService $lockService,
        CaptureInvoiceForShipment $captureInvoiceForShipment
    ): CaptureShipment {
        return $this->objectManager->create(CaptureShipment::class, [
            'lockService' => $lockService,
            'captureInvoiceForShipment' => $captureInvoiceForShipment,
        ]);
    }
}
