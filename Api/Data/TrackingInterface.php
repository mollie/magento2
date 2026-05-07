<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Api\Data;

interface TrackingInterface
{
    public const ENTITY_ID = 'entity_id';
    public const CART_ID = 'cart_id';
    public const TRACKING_DATA = 'tracking_data';
    public const CREATED_AT = 'created_at';

    public function getEntityId(): ?int;

    public function setEntityId($entityId): self;

    public function getCartId(): ?int;

    public function setCartId(int $cartId): self;

    /**
     * @return array<string, string>
     */
    public function getTrackingData(): array;

    /**
     * @param array<string, string> $trackingData
     */
    public function setTrackingData(array $trackingData): self;

    public function getCreatedAt(): ?string;

    public function setCreatedAt(string $createdAt): self;
}
