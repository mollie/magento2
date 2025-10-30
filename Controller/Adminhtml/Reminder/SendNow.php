<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Controller\Adminhtml\Reminder;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ResponseInterface;
use Mollie\Payment\Api\PendingPaymentReminderRepositoryInterface;
use Mollie\Payment\Service\Order\PaymentReminder;

class SendNow extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Mollie_Payment::sent_payment_reminders';

    public function __construct(
        Context $context,
        private PaymentReminder $paymentReminder,
        private PendingPaymentReminderRepositoryInterface $pendingPaymentReminderRepository,
    ) {
        parent::__construct($context);
    }

    public function execute(): ResponseInterface
    {
        $reminder = $this->pendingPaymentReminderRepository->get($this->getRequest()->getParam('id'));

        $order = $this->paymentReminder->send($reminder);

        $this->messageManager->addSuccessMessage(
            __('The payment reminder for order #%1 has been sent', $order->getIncrementId()),
        );

        return $this->_redirect('mollie/reminder/pending');
    }
}
