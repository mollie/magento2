<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Data;

use Magento\Framework\Api\AbstractExtensibleObject;
use Mollie\Payment\Api\Data\PaymentTokenInterface;

class PaymentToken extends AbstractExtensibleObject implements PaymentTokenInterface
{
    /**
     * Get paymenttoken_id
     * @return string|null
     */
    public function getEntityId()
    {
        return $this->_get(self::ENTITY_ID);
    }

    /**
     * Set entity_id
     * @param string $entityId
     * @return \Mollie\Payment\Api\Data\PaymentTokenInterface
     */
    public function setEntityId($entityId)
    {
        return $this->setData(self::ENTITY_ID, $entityId);
    }

    /**
     * Get cart_id
     * @return int|null
     */
    public function getCartId()
    {
        return $this->_get(self::CART_ID);
    }

    /**
     * Set quote_id
     * @param int $cartId
     * @return \Mollie\Payment\Api\Data\PaymentTokenInterface
     */
    public function setCartId($cartId)
    {
        return $this->setData(self::CART_ID, $cartId);
    }

    /**
     * Get order_id
     * @return int|null
     */
    public function getOrderId()
    {
        return $this->_get(self::ORDER_ID);
    }

    /**
     * Set order_id
     * @param int $orderId
     * @return \Mollie\Payment\Api\Data\PaymentTokenInterface
     */
    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * Get token
     * @return string|null
     */
    public function getToken()
    {
        return $this->_get(self::TOKEN);
    }

    /**
     * Set token
     * @param string $token
     * @return \Mollie\Payment\Api\Data\PaymentTokenInterface
     */
    public function setToken($token)
    {
        return $this->setData(self::TOKEN, $token);
    }

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Mollie\Payment\Api\Data\PaymentTokenExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     * @param \Mollie\Payment\Api\Data\PaymentTokenExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Mollie\Payment\Api\Data\PaymentTokenExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
