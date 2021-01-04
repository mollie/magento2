<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\Adminhtml\Reminder;

use Magento\Backend\App\AbstractAction;
use Magento\Backend\App\Action\Context;
use Mollie\Payment\Api\PendingPaymentReminderRepositoryInterface;
use Mollie\Payment\Service\Order\PaymentReminder;

class SendNow extends AbstractAction
{
    const ADMIN_RESOURCE = 'Mollie_Payment::sent_payment_reminders';

    /**
     * @var PaymentReminder
     */
    private $paymentReminder;

    /**
     * @var PendingPaymentReminderRepositoryInterface
     */
    private $pendingPaymentReminderRepository;

    public function __construct(
        Context $context,
        PaymentReminder $paymentReminder,
        PendingPaymentReminderRepositoryInterface $pendingPaymentReminderRepository
    ) {
        parent::__construct($context);

        $this->paymentReminder = $paymentReminder;
        $this->pendingPaymentReminderRepository = $pendingPaymentReminderRepository;
    }

    public function execute()
    {
        $reminder = $this->pendingPaymentReminderRepository->get($this->getRequest()->getParam('id'));

        $order = $this->paymentReminder->send($reminder);

        $this->messageManager->addSuccessMessage(
            __('The payment reminder for order #%1 has been sent', $order->getIncrementId())
        );

        return $this->_redirect('mollie/reminder/pending');
    }
}