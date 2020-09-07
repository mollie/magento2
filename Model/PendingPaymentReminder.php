<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model;

use Mollie\Payment\Api\Data\PendingPaymentReminderInterface;
use Mollie\Payment\Api\Data\PendingPaymentReminderInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;

class PendingPaymentReminder extends \Magento\Framework\Model\AbstractModel
{

    protected $pendingpaymentreminderDataFactory;

    protected $dataObjectHelper;

    protected $_eventPrefix = 'mollie_sent_payment_reminder';

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param PendingPaymentReminderInterfaceFactory $pendingpaymentreminderDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param \Mollie\Payment\Model\ResourceModel\PendingPaymentReminder $resource
     * @param \Mollie\Payment\Model\ResourceModel\PendingPaymentReminder\Collection $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        PendingPaymentReminderInterfaceFactory $pendingpaymentreminderDataFactory,
        DataObjectHelper $dataObjectHelper,
        \Mollie\Payment\Model\ResourceModel\PendingPaymentReminder $resource,
        \Mollie\Payment\Model\ResourceModel\PendingPaymentReminder\Collection $resourceCollection,
        array $data = []
    ) {
        $this->pendingpaymentreminderDataFactory = $pendingpaymentreminderDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Retrieve sentpaymentreminder model with sentpaymentreminder data
     * @return PendingPaymentReminderInterface
     */
    public function getDataModel()
    {
        $sentpaymentreminderData = $this->getData();

        $pendingpaymentreminderDataObject = $this->pendingpaymentreminderDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $pendingpaymentreminderDataObject,
            $sentpaymentreminderData,
            PendingPaymentReminderInterface::class
        );

        return $pendingpaymentreminderDataObject;
    }
}
