<?php declare(strict_types=1);

namespace Mollie\Payment\Model;

use Magento\Framework\Model\AbstractExtensibleModel;
use Mollie\Payment\Api\Data\TransactionToOrderExtensionInterface;
use Mollie\Payment\Api\Data\TransactionToOrderInterface;

class TransactionToOrder extends AbstractExtensibleModel implements TransactionToOrderInterface
{
    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(ResourceModel\TransactionToOrder::class);
    }

    /**
     * Get transaction_id
     * @return string|null
     */
    public function getTransactionId(): ?string
    {
        return $this->getData(self::TRANSACTION_ID);
    }

    /**
     * Set transaction_id
     * @param string $transaction_id
     * @return TransactionToOrderInterface
     */
    public function setTransactionId(string $transaction_id): TransactionToOrderInterface
    {
        return $this->setData(self::TRANSACTION_ID, $transaction_id);
    }

    /**
     * Get order_id
     * @return int|null
     */
    public function getOrderId(): ?int
    {
        return (int)$this->getData(self::ORDER_ID);
    }

    /**
     * Set order_id
     * @param int $order_id
     * @return TransactionToOrderInterface
     */
    public function setOrderId(int $order_id): TransactionToOrderInterface
    {
        return $this->setData(self::ORDER_ID, $order_id);
    }

    /**
     * Get created_at
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * Set created_at
     * @param string $created_at
     * @return TransactionToOrderInterface
     */
    public function setCreatedAt(string $created_at): TransactionToOrderInterface
    {
        return $this->setData(self::CREATED_AT, $created_at);
    }

    /**
     * Get skipped
     * @return int|null
     */
    public function getSkipped(): ?int
    {
        return (int)$this->getData(self::SKIPPED);
    }

    /**
     * Set skipped
     * @param int $skipped
     * @return TransactionToOrderInterface
     */
    public function setSkipped(int $skipped): TransactionToOrderInterface
    {
        return $this->setData(self::SKIPPED, $skipped);
    }

    /**
     * Get redirected
     * @return int|null
     */
    public function getRedirected(): ?int
    {
        return (int)$this->getData(self::REDIRECTED);
    }

    /**
     * Set redirected
     * @param int $redirected
     * @return TransactionToOrderInterface
     */
    public function setRedirected(int $redirected): TransactionToOrderInterface
    {
        return $this->setData(self::REDIRECTED, $redirected);
    }

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return TransactionToOrderExtensionInterface|null
     */
    public function getExtensionAttributes(): ?TransactionToOrderExtensionInterface
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     * @param TransactionToOrderExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        TransactionToOrderExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
