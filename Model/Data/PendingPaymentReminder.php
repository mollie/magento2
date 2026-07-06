<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Data;

use Magento\Framework\Api\AbstractExtensibleObject;
use Mollie\Payment\Api\Data\PendingPaymentReminderExtensionInterface;
use Mollie\Payment\Api\Data\PendingPaymentReminderInterface;

class PendingPaymentReminder extends AbstractExtensibleObject implements PendingPaymentReminderInterface
{
    /**
     * Get id
     * @return int|null
     */
    public function getEntityId(): ?int
    {
        $entityId = $this->_get(self::ENTITY_ID);

        return $entityId === null ? null : (int) $entityId;
    }

    /**
     * Set id
     * @param int $id
     * @return PendingPaymentReminderInterface
     */
    public function setEntityId(int $id)
    {
        return $this->setData(self::ENTITY_ID, $id);
    }

    /**
     * Get customer_id
     * @return int|null
     */
    public function getCustomerId(): ?int
    {
        $customerId = $this->_get(self::CUSTOMER_ID);

        return $customerId === null ? null : (int) $customerId;
    }

    /**
     * Set customer_id
     * @param int|null $customerId
     * @return PendingPaymentReminderInterface
     */
    public function setCustomerId(?int $customerId = null)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * Get hash
     * @return string|null
     */
    public function getHash()
    {
        return $this->_get(self::HASH);
    }

    /**
     * Set hash
     * @param string|null $hash
     * @return PendingPaymentReminderInterface
     */
    public function setHash(?string $hash = null)
    {
        return $this->setData(self::HASH, $hash);
    }

    /**
     * @param int $orderId
     * @return PendingPaymentReminderInterface
     */
    public function setOrderId(int $orderId)
    {
        return $this->setData(static::ORDER_ID, $orderId);
    }

    /**
     * @return int
     */
    public function getOrderId()
    {
        return $this->_get(static::ORDER_ID);
    }

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return PendingPaymentReminderExtensionInterface|null
     */
    public function getExtensionAttributes(): ?PendingPaymentReminderExtensionInterface
    {
        /** @var PendingPaymentReminderExtensionInterface|null $extensionAttributes */
        $extensionAttributes = $this->_getExtensionAttributes();

        return $extensionAttributes;
    }

    /**
     * Set an extension attributes object.
     * @param PendingPaymentReminderExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        PendingPaymentReminderExtensionInterface $extensionAttributes,
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
