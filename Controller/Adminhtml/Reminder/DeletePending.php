<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\Adminhtml\Reminder;

use Magento\Backend\App\AbstractAction;
use Magento\Backend\App\Action\Context;
use Mollie\Payment\Api\PendingPaymentReminderRepositoryInterface;

class DeletePending extends AbstractAction
{
    const ADMIN_RESOURCE = 'Mollie_Payment::pending_payment_reminders';

    /**
     * @var PendingPaymentReminderRepositoryInterface
     */
    private $pendingPaymentReminderRepository;

    public function __construct(
        Context $context,
        PendingPaymentReminderRepositoryInterface $pendingPaymentReminderRepository
    ) {
        parent::__construct($context);

        $this->pendingPaymentReminderRepository = $pendingPaymentReminderRepository;
    }

    public function execute()
    {
        $this->pendingPaymentReminderRepository->deleteById($this->getRequest()->getParam('id'));

        $this->messageManager->addSuccessMessage(__('The payment reminder has been removed'));

        return $this->_redirect('mollie/reminder/pending');
    }
}