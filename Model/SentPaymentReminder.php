<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model;

use Mollie\Payment\Api\Data\SentPaymentReminderInterface;
use Mollie\Payment\Api\Data\SentPaymentReminderInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;

class SentPaymentReminder extends \Magento\Framework\Model\AbstractModel
{

    protected $sentpaymentreminderDataFactory;

    protected $dataObjectHelper;

    protected $_eventPrefix = 'mollie_sent_payment_reminder';

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param SentPaymentReminderInterfaceFactory $sentpaymentreminderDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param \Mollie\Payment\Model\ResourceModel\SentPaymentReminder $resource
     * @param \Mollie\Payment\Model\ResourceModel\SentPaymentReminder\Collection $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        SentPaymentReminderInterfaceFactory $sentpaymentreminderDataFactory,
        DataObjectHelper $dataObjectHelper,
        \Mollie\Payment\Model\ResourceModel\SentPaymentReminder $resource,
        \Mollie\Payment\Model\ResourceModel\SentPaymentReminder\Collection $resourceCollection,
        array $data = []
    ) {
        $this->sentpaymentreminderDataFactory = $sentpaymentreminderDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Retrieve sentpaymentreminder model with sentpaymentreminder data
     * @return SentPaymentReminderInterface
     */
    public function getDataModel()
    {
        $sentpaymentreminderData = $this->getData();

        $sentpaymentreminderDataObject = $this->sentpaymentreminderDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $sentpaymentreminderDataObject,
            $sentpaymentreminderData,
            SentPaymentReminderInterface::class
        );

        return $sentpaymentreminderDataObject;
    }
}
