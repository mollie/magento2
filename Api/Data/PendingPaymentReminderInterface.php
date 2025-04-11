<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Payment\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface PendingPaymentReminderInterface extends ExtensibleDataInterface
{
    const ENTITY_ID = 'entity_id';
    const CUSTOMER_ID = 'customer_id';
    const HASH = 'hash';
    const ORDER_ID = 'order_id';

    /**
     * @param int $id
     * @return \Mollie\Payment\Api\Data\PendingPaymentReminderInterface
     */
    public function setEntityId(int $id);

    /**
     * @return int
     */
    public function getEntityId();

    /**
     * @param int|null $customerId
     * @return \Mollie\Payment\Api\Data\PendingPaymentReminderInterface
     */
    public function setCustomerId(?int $customerId = null);

    /**
     * @return int
     */
    public function getCustomerId();

    /**
     * @param string|null $hash
     * @return \Mollie\Payment\Api\Data\PendingPaymentReminderInterface
     */
    public function setHash(?string $hash = null);

    /**
     * @return string
     */
    public function getHash();

    /**
     * @param int $orderId
     * @return \Mollie\Payment\Api\Data\PendingPaymentReminderInterface
     */
    public function setOrderId(int $orderId);

    /**
     * @return int
     */
    public function getOrderId();

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Mollie\Payment\Api\Data\PendingPaymentReminderExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     * @param \Mollie\Payment\Api\Data\PendingPaymentReminderExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Mollie\Payment\Api\Data\PendingPaymentReminderExtensionInterface $extensionAttributes
    );
}
