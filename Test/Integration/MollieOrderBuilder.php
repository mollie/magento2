<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration;

use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Order;
use Mollie\Api\Resources\Payment;
use StdClass;

class MollieOrderBuilder
{
    /**
     * @var Order
     */
    private $order;

    private MollieApiClient $client;

    public function __construct()
    {
        $this->client = new MollieApiClient();
        $this->order = new Order($this->client);
    }

    public function addEmbedded(): void
    {
        if ($this->order->_embedded) {
            return;
        }

        $this->order->_embedded = new StdClass();
    }

    public function setAmount(float $value, $currency = 'EUR'): void
    {
        $this->order->amount = new StdClass();
        $this->order->amount->value = $value;
        $this->order->amount->currency = $currency;
    }

    public function addPayment(string $id): void
    {
        $this->addEmbedded();

        if (!isset($this->order->_embedded->payments)) {
            $this->order->_embedded->payments = [];
        }

        $payment = new Payment($this->client);
        $payment->id = $id;

        $this->order->_embedded->payments[] = $payment;
    }

    public function setStatus(string $status): void
    {
        $this->order->status = $status;
    }

    public function setMethod(string $method): void
    {
        $this->order->method = $method;
    }

    public function addChargeback(float $value, string $current = 'EUR'): void
    {
        if (!isset($this->order->_embedded->payments)) {
            $this->addPayment('chargeback');
        }

        $payment = $this->order->_embedded->payments[0];
        $payment->_links = new StdClass();
        $payment->_links->chargebacks = new StdClass();
    }

    public function build(): Order
    {
        return $this->order;
    }
}
