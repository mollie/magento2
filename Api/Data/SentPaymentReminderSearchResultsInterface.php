<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Payment\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

interface SentPaymentReminderSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get SentPaymentReminder list.
     * @return \Mollie\Payment\Api\Data\SentPaymentReminderInterface[]
     */
    public function getItems();

    /**
     * Set id list.
     * @param \Mollie\Payment\Api\Data\SentPaymentReminderInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}