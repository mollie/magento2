<?php

declare(strict_types=1);

namespace Mollie\Payment\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface TransactionToOrderInterface extends ExtensibleDataInterface
{
    public const TRANSACTION_ID = 'transaction_id';
    public const ORDER_ID = 'order_id';
    public const SKIPPED = 'skipped';
    public const REDIRECTED = 'redirected';
    public const CREATED_AT = 'created_at';

    /**
     * @return string|null
     */
    public function getTransactionId(): ?string;

    /**
     * @param string $transaction_id
     * @return \Mollie\Payment\Api\Data\TransactionToOrderInterface
     */
    public function setTransactionId(string $transaction_id): \Mollie\Payment\Api\Data\TransactionToOrderInterface;

    /**
     * @return int|null
     */
    public function getOrderId(): ?int;

    /**
     * @param int $order_id
     * @return \Mollie\Payment\Api\Data\TransactionToOrderInterface
     */
    public function setOrderId(int $order_id): \Mollie\Payment\Api\Data\TransactionToOrderInterface;

    /**
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * @param string $created_at
     * @return \Mollie\Payment\Api\Data\TransactionToOrderInterface
     */
    public function setCreatedAt(string $created_at): \Mollie\Payment\Api\Data\TransactionToOrderInterface;

    /**
     * @return int|null
     */
    public function getSkipped(): ?int;

    /**
     * @param int $skipped
     * @return \Mollie\Payment\Api\Data\TransactionToOrderInterface
     */
    public function setSkipped(int $skipped): \Mollie\Payment\Api\Data\TransactionToOrderInterface;

    /**
     * @return int|null
     */
    public function getRedirected(): ?int;

    /**
     * @param int $redirected
     * @return \Mollie\Payment\Api\Data\TransactionToOrderInterface
     */
    public function setRedirected(int $redirected): \Mollie\Payment\Api\Data\TransactionToOrderInterface;

    /**
     * @return \Mollie\Payment\Api\Data\TransactionToOrderExtensionInterface|null
     */
    public function getExtensionAttributes(): ?\Mollie\Payment\Api\Data\TransactionToOrderExtensionInterface;

    /**
     * @param \Mollie\Payment\Api\Data\TransactionToOrderExtensionInterface $extensionAttributes
     * @return static
     */
    public function setExtensionAttributes(
        \Mollie\Payment\Api\Data\TransactionToOrderExtensionInterface $extensionAttributes
    );
}
