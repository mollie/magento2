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
use Mollie\Payment\Api\SentPaymentReminderRepositoryInterface;

class DeleteSent extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Mollie_Payment::sent_payment_reminders';

    public function __construct(
        Context $context,
        private SentPaymentReminderRepositoryInterface $sentPaymentReminderRepository,
    ) {
        parent::__construct($context);
    }

    public function execute(): ResponseInterface
    {
        $this->sentPaymentReminderRepository->deleteById($this->getRequest()->getParam('id'));

        $this->messageManager->addSuccessMessage(__('The payment reminder has been removed'));

        return $this->_redirect('mollie/reminder/sent');
    }
}
