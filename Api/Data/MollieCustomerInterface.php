<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface MollieCustomerInterface extends ExtensibleDataInterface
{
    const ENTITY_ID = 'entity_id';
    const CUSTOMER_ID = 'customer_id';
    const MOLLIE_CUSTOMER_ID = 'mollie_customer_id';

    /**
     * Get customer_id
     * @return string|null
     */
    public function getEntityId();

    /**
     * Set customer_id
     * @param string $entityId
     * @return \Mollie\Payment\Api\Data\MollieCustomerInterface
     */
    public function setEntityId($entityId);

    /**
     * Get customer_id
     * @return int|null
     */
    public function getCustomerId();

    /**
     * Set customer_id
     * @param int $customerId
     * @return \Mollie\Payment\Api\Data\MollieCustomerInterface
     */
    public function setCustomerId($customerId);

    /**
     * Get mollie_customer_id
     * @return text|null
     */
    public function getMollieCustomerId();

    /**
     * Set mollie_customer_id
     * @param int $mollieMollieCustomerId
     * @return \Mollie\Payment\Api\Data\MollieCustomerInterface
     */
    public function setMollieCustomerId($mollieMollieCustomerId);

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Mollie\Payment\Api\Data\MollieCustomerExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     * @param \Mollie\Payment\Api\Data\MollieCustomerExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Mollie\Payment\Api\Data\MollieCustomerExtensionInterface $extensionAttributes
    );
}
