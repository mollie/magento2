<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Payment\Cron;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\Order;
use Mollie\Payment\Api\SentPaymentReminderRepositoryInterface;
use Mollie\Payment\Config;

class RemoveSentPaymentReminders
{
    /**
     * @var SentPaymentReminderRepositoryInterface
     */
    private $sentPaymentReminderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $builder;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var Config
     */
    private $config;

    public function __construct(
        SentPaymentReminderRepositoryInterface $sentPaymentReminderRepository,
        SearchCriteriaBuilder $builder,
        DateTime $dateTime,
        Config $config
    ) {
        $this->sentPaymentReminderRepository = $sentPaymentReminderRepository;
        $this->builder = $builder;
        $this->dateTime = $dateTime;
        $this->config = $config;
    }

    public function execute()
    {
        try {
            $this->deletePaymentReminders();
        } catch (\Throwable $exception) {
            $this->config->addToLog('error', 'Error while running ' . static::class);
            $this->config->addToLog('error', (string)$exception);

            throw $exception;
        }
    }

    protected function deletePaymentReminders(): void
    {
        do {
            $date = (new \DateTimeImmutable($this->dateTime->gmtDate()))->sub(new \DateInterval('P1W'));
            $this->builder->addFilter(Order::CREATED_AT, $date, 'lt');
            $this->builder->setPageSize(10);

            $result = $this->sentPaymentReminderRepository->getList($this->builder->create());

            foreach ($result->getItems() as $item) {
                $this->sentPaymentReminderRepository->delete($item);
            }
        } while ($result->getTotalCount());
    }
}
