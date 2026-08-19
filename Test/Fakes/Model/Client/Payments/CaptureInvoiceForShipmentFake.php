<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Fakes\Model\Client\Payments;

use Exception;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\ShipmentInterface;
use Mollie\Payment\Model\Client\Payments\CaptureInvoiceForShipment;

class CaptureInvoiceForShipmentFake extends CaptureInvoiceForShipment
{
    private int $timesCalled = 0;

    private ?Exception $exceptionToThrow = null;

    /**
     * @var callable|null
     */
    private $duringCapture = null;

    public function givenTheCaptureFailsWith(Exception $exception): void
    {
        $this->exceptionToThrow = $exception;
    }

    public function givenThisRunsDuringTheCapture(callable $callback): void
    {
        $this->duringCapture = $callback;
    }

    public function getTimesCalled(): int
    {
        return $this->timesCalled;
    }

    public function execute(ShipmentInterface $shipment): ?InvoiceInterface
    {
        $this->timesCalled++;

        if ($this->duringCapture) {
            ($this->duringCapture)();
        }

        if ($this->exceptionToThrow) {
            throw $this->exceptionToThrow;
        }

        return null;
    }
}
