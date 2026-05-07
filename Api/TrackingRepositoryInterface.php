<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Api;

use Mollie\Payment\Api\Data\TrackingInterface;

interface TrackingRepositoryInterface
{
    public function save(TrackingInterface $tracking): TrackingInterface;

    public function getByCartId(int $cartId): ?TrackingInterface;

    /**
     * @return array<string, string>
     */
    public function getTrackingDataByCartId(int $cartId): array;
}
