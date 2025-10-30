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
use Magento\Ui\Component\MassAction\Filter;
use Mollie\Payment\Api\SentPaymentReminderRepositoryInterface;
use Mollie\Payment\Model\ResourceModel\SentPaymentReminder\CollectionFactory;
use Mollie\Payment\Model\SentPaymentReminder;

class DeleteSentMassAction extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Mollie_Payment::sent_payment_reminders';

    public function __construct(
        Context $context,
        private SentPaymentReminderRepositoryInterface $sentPaymentReminderRepository,
        private Filter $filter,
        private CollectionFactory $collectionFactory,
    ) {
        parent::__construct($context);
    }

    public function execute(): ResponseInterface
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
