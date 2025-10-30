<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Cron;

use DateInterval;
use DateTimeImmutable;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\Order;
use Mollie\Payment\Api\SentPaymentReminderRepositoryInterface;
use Mollie\Payment\Config;
use Throwable;

class RemoveSentPaymentReminders
{
    public function __construct(
        private SentPaymentReminderRepositoryInterface $sentPaymentReminderRepository,
        private SearchCriteriaBuilder $builder,
        private DateTime $dateTime,
        private Config $config
    ) {}

    public function execute(): void
    {
        try {
            $this->deletePaymentReminders();
        } catch (Throwable $exception) {
            $this->config->addToLog('error', 'Error while running ' . static::class);
            $this->config->addToLog('error', (string) $exception);

            throw $exception;
        }
    }

    protected function deletePaymentReminders(): void
    {
        do {
            $date = (new DateTimeImmutable($this->dateTime->gmtDate()))->sub(new DateInterval('P1W'));
            $this->builder->addFilter(Order::CREATED_AT, $date, 'lt');
            $this->builder->setPageSize(10);

            $result = $this->sentPaymentReminderRepository->getList($this->builder->create());

            foreach ($result->getItems() as $item) {
                $this->sentPaymentReminderRepository->delete($item);
            }
        } while ($result->getTotalCount());
    }
}
