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
use Mollie\Payment\Service\Order\PaymentReminder;

class SendNowMassAction extends AbstractAction
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
        PaymentReminder $paymentReminder,
        PendingPaymentReminderRepositoryInterface $pendingPaymentReminderRepository,
        Filter $filter,
        CollectionFactory $collectionFactory
    ) {
        parent::__construct($context);

        $this->paymentReminder = $paymentReminder;
        $this->pendingPaymentReminderRepository = $pendingPaymentReminderRepository;
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
    }

    public function execute()
    {
        $collection = $this->filter->getCollection($this->collectionFactory->create());

        /** @var PendingPaymentReminder $item */
        foreach ($collection->getItems() as $item) {
            $this->paymentReminder->send($item->getDataModel());
        }

        $this->messageManager->addSuccessMessage(
            __('The payment reminder for %1 order(s) has been sent', count($collection->getItems()))
        );

        return $this->_redirect('mollie/reminder/pending');
    }
}