<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order;

use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Mollie\Payment\Api\Data\PendingPaymentReminderInterface;
use Mollie\Payment\Api\PendingPaymentReminderRepositoryInterface;
use Mollie\Payment\Config;

class DeletePaymentReminder
{
    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $criteriaBuilderFactory;
    /**
     * @var PendingPaymentReminderRepositoryInterface
     */
    private $paymentReminderRepository;
    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    public function __construct(
        EncryptorInterface $encryptor,
        SearchCriteriaBuilderFactory $criteriaBuilderFactory,
        PendingPaymentReminderRepositoryInterface $paymentReminderRepository
    ) {
        $this->criteriaBuilderFactory = $criteriaBuilderFactory;
        $this->paymentReminderRepository = $paymentReminderRepository;
        $this->encryptor = $encryptor;
    }

    /**
     * Delete payment reminders by reference
     * This reference can be a customer ID or Email Address
     *
     * @param string|int|null $reference
     */
    public function delete($reference)
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
