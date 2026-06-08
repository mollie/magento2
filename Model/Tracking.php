<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model;

use Magento\Framework\Model\AbstractModel;
use Mollie\Payment\Api\Data\TrackingInterface;

class Tracking extends AbstractModel implements TrackingInterface
{
    protected $_eventPrefix = 'mollie_payment_tracking';

    protected function _construct()
    {
        $this->_init(ResourceModel\Tracking::class);
    }

    public function getEntityId(): ?int
    {
        $value = $this->_getData(self::ENTITY_ID);

        return $value === null ? null : (int) $value;
    }

    public function setEntityId($entityId): TrackingInterface
    {
        $this->setData(self::ENTITY_ID, $entityId);

        return $this;
    }

    public function getCartId(): ?int
    {
        $value = $this->_getData(self::CART_ID);

        return $value === null ? null : (int) $value;
    }

    public function setCartId(int $cartId): TrackingInterface
    {
        $this->setData(self::CART_ID, $cartId);

        return $this;
    }

    public function getTrackingData(): array
    {
        $raw = $this->_getData(self::TRACKING_DATA);
        if (!$raw) {
            return [];
        }

        $decoded = json_decode((string) $raw, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function setTrackingData(array $trackingData): TrackingInterface
    {
        $this->setData(self::TRACKING_DATA, $trackingData ? json_encode($trackingData) : null);

        return $this;
    }

    public function getCreatedAt(): ?string
    {
        $value = $this->_getData(self::CREATED_AT);

        return $value === null ? null : (string) $value;
    }

    public function setCreatedAt(string $createdAt): TrackingInterface
    {
        $this->setData(self::CREATED_AT, $createdAt);

        return $this;
    }
}
