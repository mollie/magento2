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

    public function __construct(
        SentPaymentReminderRepositoryInterface $sentPaymentReminderRepository,
        SearchCriteriaBuilder $builder,
        DateTime $dateTime
    ) {
        $this->sentPaymentReminderRepository = $sentPaymentReminderRepository;
        $this->builder = $builder;
        $this->dateTime = $dateTime;
    }

    public function execute()
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