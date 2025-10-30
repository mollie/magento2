<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Queue;

use Mollie\Payment\Api\Data\TransactionToProcessInterface;

class TransactionToProcess implements TransactionToProcessInterface
{
    private ?string $transactionId = null;

    private ?int $orderId = null;

    private ?string $type = null;

    public function setTransactionId(string $id): TransactionToProcessInterface
    {
        $this->transactionId = $id;

        return $this;
    }

    public function getTransactionId(): ?string
    {
        return $this->transactionId;
    }

    public function setOrderId(int $id): TransactionToProcessInterface
    {
        $this->orderId = $id;

        return $this;
    }

    public function getOrderId(): ?int
    {
        return $this->orderId;
    }

    public function setType(string $type): TransactionToProcessInterface
    {
        $this->type = $type;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }
}
