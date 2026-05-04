<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Api\Webapi;

interface SavedCardsInterface
{
    /**
     * List saved credit cards (mandates) for the currently authenticated customer.
     *
     * @return \Mollie\Payment\Api\Data\SavedCardInterface[]
     */
    public function getList(): array;

    /**
     * Revoke a saved credit card mandate.
     *
     * @param string $mandateId
     * @return bool
     */
    public function delete(string $mandateId): bool;
}
