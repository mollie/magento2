<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Api\Data;

interface TransactionToProcessInterface
{
    /**
     * @param string $id
     * @return \Mollie\Payment\Api\Data\TransactionToProcessInterface
     */
    public function setTransactionId(string $id): TransactionToProcessInterface;

    /**
     * @return string
     */
    public function getTransactionId(): ?string;

    /**
     * @param int $id
     * @return \Mollie\Payment\Api\Data\TransactionToProcessInterface
     */
    public function setOrderId(int $id): TransactionToProcessInterface;

    /**
     * @return int|null
     */
    public function getOrderId(): ?int;

    /**
     * @param string $type
     * @return \Mollie\Payment\Api\Data\TransactionToProcessInterface
     */
    public function setType(string $type): TransactionToProcessInterface;

    /**
     * @return string|null
     */
    public function getType(): ?string;
}
