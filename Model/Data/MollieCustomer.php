<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Data;

use Magento\Framework\Api\AbstractExtensibleObject;
use Mollie\Payment\Api\Data\MollieCustomerInterface;

class MollieCustomer extends AbstractExtensibleObject implements MollieCustomerInterface
{
    /**
     * Get customer_id
     * @return string|null
     */
    public function getEntityId()
    {
        return $this->_get(self::ENTITY_ID);
    }

    /**
     * Set entity_id
     * @param string $entityId
     * @return \Mollie\Payment\Api\Data\MollieCustomerInterface
     */
    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * Get customer_id
     * @return int|null
     */
    public function getCustomerId()
    {
        return $this->_get(self::CUSTOMER_ID);
    }

    /**
     * Set customer_id
     * @param int $customerId
     * @return \Mollie\Payment\Api\Data\MollieCustomerInterface
     */
    public function setCustomerId($customerId)
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * Get customer_id
     * @return int|null
     */
    public function getMollieCustomerId()
    {
        return $this->_get(self::MOLLIE_CUSTOMER_ID);
    }

    /**
     * Set customer_id
     * @param int $mollieCustomerId
     * @return \Mollie\Payment\Api\Data\MollieCustomerInterface
     */
    public function setMollieCustomerId($mollieCustomerId)
    {
        return $this->setData(self::MOLLIE_CUSTOMER_ID, $mollieCustomerId);
    }

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Mollie\Payment\Api\Data\MollieCustomerExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     * @param \Mollie\Payment\Api\Data\MollieCustomerExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Mollie\Payment\Api\Data\MollieCustomerExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
