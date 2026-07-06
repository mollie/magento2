<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Data;

use Magento\Framework\Api\AbstractExtensibleObject;
use Mollie\Payment\Api\Data\SentPaymentReminderExtensionInterface;
use Mollie\Payment\Api\Data\SentPaymentReminderInterface;

class SentPaymentReminder extends AbstractExtensibleObject implements SentPaymentReminderInterface
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
     * @return SentPaymentReminderInterface
     */
    public function setEntityId(int $id)
    {
        return $this->setData(self::ENTITY_ID, $id);
    }

    /**
     * @param int $orderId
     * @return SentPaymentReminderInterface
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
     * @return SentPaymentReminderExtensionInterface|null
     */
    public function getExtensionAttributes(): ?SentPaymentReminderExtensionInterface
    {
        /** @var SentPaymentReminderExtensionInterface|null $extensionAttributes */
        $extensionAttributes = $this->_getExtensionAttributes();

        return $extensionAttributes;
    }

    /**
     * Set an extension attributes object.
     * @param SentPaymentReminderExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        SentPaymentReminderExtensionInterface $extensionAttributes,
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
