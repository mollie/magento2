<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Data;

use Magento\Framework\Api\AbstractExtensibleObject;
use Mollie\Payment\Api\Data\PendingPaymentReminderExtensionInterface;
use Mollie\Payment\Api\Data\PendingPaymentReminderInterface;

class PendingPaymentReminder extends AbstractExtensibleObject implements PendingPaymentReminderInterface
{
    /**
     * Get id
     * @return string|null
     */
    public function getEntityId()
    {
        return $this->_get(self::ENTITY_ID);
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
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     * @param PendingPaymentReminderExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        PendingPaymentReminderExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
