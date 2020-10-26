<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Payment\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Mollie\Payment\Api\Data\SentPaymentReminderInterface;
use Mollie\Payment\Api\Data\SentPaymentReminderSearchResultsInterface;

interface SentPaymentReminderRepositoryInterface
{
    /**
     * @param int $id
     * @return SentPaymentReminderInterface
     */
    public function get(int $id);

    /**
     * @param int $orderId
     * @return SentPaymentReminderInterface
     */
    public function getByOrderId(int $orderId);

    /**
     * @param SearchCriteriaInterface $criteria
     * @return SentPaymentReminderSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $criteria);

    /**
     * @param SentPaymentReminderInterface $entity
     * @return SentPaymentReminderInterface
     */
    public function save(SentPaymentReminderInterface $entity);

    /**
     * @param SentPaymentReminderInterface $entity
     * @return SentPaymentReminderInterface
     */
    public function delete(SentPaymentReminderInterface $entity);

    /**
     * @param int $id
     * @return SentPaymentReminderInterface
     */
    public function deleteById(int $id);
}
