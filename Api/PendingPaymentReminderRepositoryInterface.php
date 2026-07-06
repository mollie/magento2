<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Mollie\Payment\Api\Data\PendingPaymentReminderInterface;
use Mollie\Payment\Api\Data\PendingPaymentReminderSearchResultsInterface;

interface PendingPaymentReminderRepositoryInterface
{
    /**
     * @param int $id
     * @return PendingPaymentReminderInterface
     */
    public function get(int $id);

    /**
     * @param SearchCriteriaInterface $criteria
     * @return PendingPaymentReminderSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $criteria);

    /**
     * @param int $id
     * @return PendingPaymentReminderInterface
     */
    public function getByOrderId(int $id);

    /**
     * @param PendingPaymentReminderInterface $entity
     * @return PendingPaymentReminderInterface
     */
    public function save(PendingPaymentReminderInterface $entity);

    /**
     * @param PendingPaymentReminderInterface $entity
     * @return bool
     */
    public function delete(PendingPaymentReminderInterface $entity): bool;

    /**
     * @param int $id
     * @return bool
     */
    public function deleteById(int $id): bool;

    /**
     * @param int $id
     * @return bool
     */
    public function deleteByOrderId(int $id): bool;
}
