<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\Adminhtml\Reminder;

use Magento\Backend\App\AbstractAction;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Mollie\Payment\Api\PendingPaymentReminderRepositoryInterface;
use Mollie\Payment\Model\PendingPaymentReminder;
use Mollie\Payment\Model\ResourceModel\PendingPaymentReminder\CollectionFactory;

class DeletePendingMassAction extends AbstractAction
{
    const ADMIN_RESOURCE = 'Mollie_Payment::pending_payment_reminders';

    /**
     * @var PendingPaymentReminderRepositoryInterface
     */
    private $pendingPaymentReminderRepository;

    /**
     * @var Filter
     */
    private $filter;

    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    public function __construct(
        Context $context,
        PendingPaymentReminderRepositoryInterface $pendingPaymentReminderRepository,
        Filter $filter,
        CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);

        $this->pendingPaymentReminderRepository = $pendingPaymentReminderRepository;
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
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