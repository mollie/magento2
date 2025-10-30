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
use Mollie\Payment\Service\Order\PaymentReminder;

class SendNowMassAction extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Mollie_Payment::sent_payment_reminders';

    public function __construct(
        Context $context,
        private PaymentReminder $paymentReminder,
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
            $this->paymentReminder->send($item->getDataModel());
        }

        $this->messageManager->addSuccessMessage(
            __('The payment reminder for %1 order(s) has been sent', count($collection->getItems())),
        );

        return $this->_redirect('mollie/reminder/pending');
    }
}
