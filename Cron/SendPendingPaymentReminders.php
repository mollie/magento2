<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Payment\Cron;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrder;
use Magento\Framework\Api\SortOrderFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Model\Order;
use Mollie\Payment\Api\PendingPaymentReminderRepositoryInterface;
use Mollie\Payment\Service\Order\PaymentReminder;

class SendPendingPaymentReminders
{
    /**
     * @var PendingPaymentReminderRepositoryInterface
     */
    private $paymentReminderRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    private $builder;

    /**
     * @var SortOrderFactory
     */
    private $sortOrderFactory;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var PaymentReminder
     */
    private $paymentReminder;

    public function __construct(
        PendingPaymentReminderRepositoryInterface $paymentReminderRepository,
        SearchCriteriaBuilder $builder,
        SortOrderFactory $sortOrderFactory,
        DateTime $dateTime,
        PaymentReminder $paymentReminder
    ) {
        $this->paymentReminderRepository = $paymentReminderRepository;
        $this->builder = $builder;
        $this->sortOrderFactory = $sortOrderFactory;
        $this->dateTime = $dateTime;
        $this->paymentReminder = $paymentReminder;
    }

    public function execute()
    {
        do {
            /** @var SortOrder $sortOrder */
            $sortOrder = $this->sortOrderFactory->create();
            $sortOrder->setField('entity_id');
            $sortOrder->setDirection(SortOrder::SORT_ASC);

            $date = (new \DateTimeImmutable($this->dateTime->gmtDate()))->sub(new \DateInterval('PT1H'));
            $this->builder->addFilter(Order::CREATED_AT, $date, 'lt');
            $this->builder->addSortOrder($sortOrder);
            $this->builder->setPageSize(10);

            $result = $this->paymentReminderRepository->getList($this->builder->create());

            foreach ($result->getItems() as $item) {
                $this->paymentReminder->send($item);
            }
        } while ($result->getTotalCount());
    }
}