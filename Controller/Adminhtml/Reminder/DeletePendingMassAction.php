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
use Magento\Ui\Component\MassAction\Filter;
use Mollie\Payment\Api\PendingPaymentReminderRepositoryInterface;
use Mollie\Payment\Model\PendingPaymentReminder;
use Mollie\Payment\Model\ResourceModel\PendingPaymentReminder\CollectionFactory;

class DeletePendingMassAction extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Mollie_Payment::pending_payment_reminders';

    public function __construct(
        Context $context,
        private PendingPaymentReminderRepositoryInterface $pendingPaymentReminderRepository,
        private Filter $filter,
        private CollectionFactory $collectionFactory,
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());

        /** @var PendingPaymentReminder $item */
        foreach ($collection->getItems() as $item) {
            $this->pendingPaymentReminderRepository->deleteById($item->getData('entity_id'));
        }

        $this->messageManager->addSuccessMessage(__('The selected payment reminders have been removed'));

        return $this->_redirect('mollie/reminder/pending');
    }
}
