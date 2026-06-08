<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Order;

use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Encryption\EncryptorInterface;
use Mollie\Payment\Api\Data\PendingPaymentReminderInterface;
use Mollie\Payment\Api\PendingPaymentReminderRepositoryInterface;

class DeletePaymentReminder
{
    public function __construct(
        private EncryptorInterface $encryptor,
        private SearchCriteriaBuilderFactory $criteriaBuilderFactory,
        private PendingPaymentReminderRepositoryInterface $paymentReminderRepository,
    ) {}

    public function deleteByCustomerId(int $customerId): void
    {
        $criteria = $this->criteriaBuilderFactory->create();
        $criteria->addFilter(PendingPaymentReminderInterface::CUSTOMER_ID, $customerId);

        foreach ($this->paymentReminderRepository->getList($criteria->create())->getItems() as $reminder) {
            $this->paymentReminderRepository->delete($reminder);
        }
    }

    public function deleteByEmail(string $email): void
    {
        if ($email === '') {
            return;
        }

        $criteria = $this->criteriaBuilderFactory->create();
        $criteria->addFilter(PendingPaymentReminderInterface::CUSTOMER_ID, '', 'null');
        $criteria->addFilter(PendingPaymentReminderInterface::HASH, $this->encryptor->hash($email));

        foreach ($this->paymentReminderRepository->getList($criteria->create())->getItems() as $reminder) {
            $this->paymentReminderRepository->delete($reminder);
        }
    }
}
