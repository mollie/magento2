<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface PaymentTokenInterface extends ExtensibleDataInterface
{
    const ENTITY_ID = 'entity_id';
    const CART_ID = 'cart_id';
    const ORDER_ID = 'order_id';
    const TOKEN = 'token';

    /**
     * Get paymenttoken_id
     * @return string|null
     */
    public function getEntityId();

    /**
     * Set paymenttoken_id
     * @param string $entityId
     * @return \Mollie\Payment\Api\Data\PaymentTokenInterface
     */
    public function setEntityId($entityId);

    /**
     * Get cart_id
     * @return int|null
     */
    public function getCartId();

    /**
     * Set cart_id
     * @param int $cartId
     * @return \Mollie\Payment\Api\Data\PaymentTokenInterface
     */
    public function setCartId($cartId);

    /**
     * Get order_id
     * @return int|null
     */
    public function getOrderId();

    /**
     * Set order_id
     * @param int $orderId
     * @return \Mollie\Payment\Api\Data\PaymentTokenInterface
     */
    public function setOrderId($orderId);

    /**
     * Get token
     * @return string|null
     */
    public function getToken();

    /**
     * Set token
     * @param string $token
     * @return \Mollie\Payment\Api\Data\PaymentTokenInterface
     */
    public function setToken($token);

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Mollie\Payment\Api\Data\PaymentTokenExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     * @param \Mollie\Payment\Api\Data\PaymentTokenExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Mollie\Payment\Api\Data\PaymentTokenExtensionInterface $extensionAttributes
    );
}
