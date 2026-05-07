<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Api\Data;

interface SavedCardInterface
{
    /**
     * @return string
     */
    public function getMandateId(): string;

    /**
     * @return string
     */
    public function getCardLabel(): string;

    /**
     * @return string
     */
    public function getCardNumberLast4(): string;

    /**
     * @return string|null
     */
    public function getCardExpiryDate(): ?string;

    /**
     * @return string|null
     */
    public function getCardHolder(): ?string;
}
