<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Payment\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface PendingPaymentReminderSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get PendingPaymentReminder list.
     * @return \Mollie\Payment\Api\Data\PendingPaymentReminderInterface[]
     */
    public function getItems();

    /**
     * Set id list.
     * @param \Mollie\Payment\Api\Data\PendingPaymentReminderInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}
