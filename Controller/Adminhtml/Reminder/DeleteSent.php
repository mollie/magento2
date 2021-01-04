<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\Adminhtml\Reminder;

use Magento\Backend\App\AbstractAction;
use Magento\Backend\App\Action\Context;
use Mollie\Payment\Api\SentPaymentReminderRepositoryInterface;

class DeleteSent extends AbstractAction
{
    const ADMIN_RESOURCE = 'Mollie_Payment::sent_payment_reminders';

    /**
     * @var SentPaymentReminderRepositoryInterface
     */
    private $sentPaymentReminderRepository;

    public function __construct(
        Context $context,
        SentPaymentReminderRepositoryInterface $sentPaymentReminderRepository
    ) {
        parent::__construct($context);

        $this->sentPaymentReminderRepository = $sentPaymentReminderRepository;
    }

    public function execute()
    {
        $this->sentPaymentReminderRepository->deleteById($this->getRequest()->getParam('id'));

        $this->messageManager->addSuccessMessage(__('The payment reminder has been removed'));

        return $this->_redirect('mollie/reminder/sent');
    }
}