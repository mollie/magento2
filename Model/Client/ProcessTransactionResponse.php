<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Client;

class ProcessTransactionResponse
{
    /**
     * @var bool
     */
    private $success;

    /**
     * @var string
     */
    private $status;

    /**
     * @var string
     */
    private $order_id;

    /**
     * @var string
     */
    private $type;

    public function __construct(
        bool $success,
        string $status,
        string $order_id,
        string $type
    ) {
        $this->success = $success;
        $this->status = $status;
        $this->order_id = $order_id;
        $this->type = $type;
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @return string
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @return string
     */
    public function getOrderId(): string
    {
        return $this->order_id;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'status' => $this->status,
            'order_id' => $this->order_id,
            'type' => $this->type,
        ];
    }
}
