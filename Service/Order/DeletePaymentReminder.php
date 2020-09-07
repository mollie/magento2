<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Order;

use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Mollie\Payment\Api\PendingPaymentReminderRepositoryInterface;

class DeletePaymentReminder
{
    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $criteriaBuilderFactory;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var PendingPaymentReminderRepositoryInterface
     */
    private $paymentReminderRepository;

    public function __construct(
        DateTime $dateTime,
        SearchCriteriaBuilderFactory $criteriaBuilderFactory,
        OrderRepositoryInterface $orderRepository,
        PendingPaymentReminderRepositoryInterface $paymentReminderRepository
    ) {
        $this->dateTime = $dateTime;
        $this->criteriaBuilderFactory = $criteriaBuilderFactory;
        $this->orderRepository = $orderRepository;
        $this->paymentReminderRepository = $paymentReminderRepository;
    }

    public function byEmail(string $email)
    {
        $date = (new \DateTimeImmutable($this->dateTime->gmtDate()))->sub(new \DateInterval('PT1H'));

        $criteria = $this->criteriaBuilderFactory->create();
        $criteria->addFilter(Order::CUSTOMER_EMAIL, $email);
        $criteria->addFilter(Order::CREATED_AT, $date, 'gt');

        $orders = $this->orderRepository->getList($criteria->create());
        $ids = array_keys($orders->getItems());

        foreach ($ids as $orderId) {
            try {
                $this->paymentReminderRepository->deleteByOrderId($orderId);
            } catch (NoSuchEntityException $exception) {
                // Silence is golden
            }
        }
    }
}