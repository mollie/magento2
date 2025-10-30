<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order;

use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Mollie\Payment\Api\Data\PendingPaymentReminderInterface;
use Mollie\Payment\Api\PendingPaymentReminderRepositoryInterface;

class DeletePaymentReminder
{
    public function __construct(
        private EncryptorInterface $encryptor,
        private SearchCriteriaBuilderFactory $criteriaBuilderFactory,
        private PendingPaymentReminderRepositoryInterface $paymentReminderRepository
    ) {}

    /**
     * Delete payment reminders by reference
     * This reference can be a customer ID or Email Address
     *
     * @param string|int|null $reference
     */
    public function delete($reference): void
    {
        if (empty($reference)) {
            return;
        }

        $criteria = $this->criteriaBuilderFactory->create();
        if (is_numeric($reference)) {
            $criteria->addFilter(PendingPaymentReminderInterface::CUSTOMER_ID, $reference);
        } else {
            $criteria->addFilter(PendingPaymentReminderInterface::CUSTOMER_ID, '', 'null');
            $criteria->addFilter(PendingPaymentReminderInterface::HASH, $this->encryptor->hash($reference));
        }

        $reminders = $this->paymentReminderRepository->getList($criteria->create());
        foreach ($reminders->getItems() as $reminder) {
            try {
                $this->paymentReminderRepository->delete($reminder);
            } catch (NoSuchEntityException $exception) {
                // Silence is golden
            }
        }
    }
}
