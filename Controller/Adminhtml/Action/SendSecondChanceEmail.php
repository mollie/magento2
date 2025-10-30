<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Controller\Adminhtml\Action;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Api\Data\SentPaymentReminderInterfaceFactory;
use Mollie\Payment\Api\SentPaymentReminderRepositoryInterface;
use Mollie\Payment\Service\Order\SecondChanceEmail;

class SendSecondChanceEmail extends Action implements HttpPostActionInterface
{
    public function __construct(
        Context $context,
        private OrderRepositoryInterface $orderRepository,
        private SecondChanceEmail $secondChanceEmail,
        private SentPaymentReminderRepositoryInterface $sentPaymentReminderRepository,
        private SentPaymentReminderInterfaceFactory $sentPaymentReminderFactory,
    ) {
        parent::__construct($context);
    }

    public function execute(): ResponseInterface
    {
        $id = $this->getRequest()->getParam('id');
        $order = $this->orderRepository->get($id);

        $this->secondChanceEmail->send($order);

        try {
            $reminder = $this->sentPaymentReminderFactory->create();
            $reminder->setOrderId($order->getEntityId());
            $this->sentPaymentReminderRepository->save($reminder);
        } catch (CouldNotSaveException) {
            // It might already exist
        }

        $this->messageManager->addSuccessMessage(__('The payment reminder email was successfully send'));

        return $this->_redirect($this->_redirect->getRefererUrl());
    }
}
