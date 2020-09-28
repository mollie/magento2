<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\Adminhtml\Action;

use Magento\Backend\App\Action;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Api\Data\SentPaymentReminderInterfaceFactory;
use Mollie\Payment\Api\SentPaymentReminderRepositoryInterface;
use Mollie\Payment\Service\Order\SecondChanceEmail;

class SendSecondChanceEmail extends Action
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var SecondChanceEmail
     */
    private $secondChanceEmail;

    /**
     * @var SentPaymentReminderRepositoryInterface
     */
    private $sentPaymentReminderRepository;

    /**
     * @var SentPaymentReminderInterfaceFactory
     */
    private $sentPaymentReminderFactory;

    public function __construct(
        Action\Context $context,
        OrderRepositoryInterface $orderRepository,
        SecondChanceEmail $secondChanceEmail,
        SentPaymentReminderRepositoryInterface $sentPaymentReminderRepository,
        SentPaymentReminderInterfaceFactory $sentPaymentReminderFactory
    ) {
        parent::__construct($context);
        $this->secondChanceEmail = $secondChanceEmail;
        $this->orderRepository = $orderRepository;
        $this->sentPaymentReminderRepository = $sentPaymentReminderRepository;
        $this->sentPaymentReminderFactory = $sentPaymentReminderFactory;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('id');
        $order = $this->orderRepository->get($id);

        $this->secondChanceEmail->send($order);

        try {
            $reminder = $this->sentPaymentReminderFactory->create();
            $reminder->setOrderId($order->getEntityId());
            $this->sentPaymentReminderRepository->save($reminder);
        } catch (CouldNotSaveException $exception) {
            // It might already exist
        }

        $this->messageManager->addSuccessMessage(__('The payment reminder email was successfully send'));

        return $this->_redirect($this->_redirect->getRefererUrl());
    }
}
