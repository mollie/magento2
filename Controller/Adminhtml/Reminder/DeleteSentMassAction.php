<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\Adminhtml\Reminder;

use Magento\Backend\App\AbstractAction;
use Magento\Backend\App\Action\Context;
use Magento\Ui\Component\MassAction\Filter;
use Mollie\Payment\Api\SentPaymentReminderRepositoryInterface;
use Mollie\Payment\Model\ResourceModel\SentPaymentReminder\CollectionFactory;
use Mollie\Payment\Model\SentPaymentReminder;

class DeleteSentMassAction extends AbstractAction
{
    const ADMIN_RESOURCE = 'Mollie_Payment::sent_payment_reminders';

    /**
     * @var SentPaymentReminderRepositoryInterface
     */
    private $sentPaymentReminderRepository;

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
        SentPaymentReminderRepositoryInterface $sentPaymentReminderRepository,
        Filter $filter,
        CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);

        $this->sentPaymentReminderRepository = $sentPaymentReminderRepository;
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
    }

    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());

        /** @var SentPaymentReminder $item */
        foreach ($collection->getItems() as $item) {
            $this->sentPaymentReminderRepository->deleteById($item->getData('entity_id'));
        }

        $this->messageManager->addSuccessMessage(__('The selected payment reminders have been removed'));

        return $this->_redirect('mollie/reminder/sent');
    }
}