<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Fakes\Model\Order\Email;

use Magento\Framework\Exception\MailException;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

class OrderSenderFake extends OrderSender
{
    private bool $shouldFailTransport = false;

    private ?MailException $exception = null;

    public function givenTransportFails(): void
    {
        $this->shouldFailTransport = true;
    }

    public function givenSendThrows(MailException $exception): void
    {
        $this->exception = $exception;
    }

    public function send(Order $order, $forceSyncMode = false)
    {
        if ($this->exception !== null) {
            throw $this->exception;
        }

        if ($this->shouldFailTransport) {
            $order->setSendEmail(true);
            return false;
        }

        return parent::send($order, $forceSyncMode);
    }
}
