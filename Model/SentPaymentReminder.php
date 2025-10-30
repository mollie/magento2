<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Mollie\Payment\Api\Data\SentPaymentReminderInterface;
use Mollie\Payment\Api\Data\SentPaymentReminderInterfaceFactory;
use Mollie\Payment\Model\ResourceModel\SentPaymentReminder\Collection;

class SentPaymentReminder extends AbstractModel
{
    protected SentPaymentReminderInterfaceFactory $sentpaymentreminderDataFactory;

    protected $_eventPrefix = 'mollie_sent_payment_reminder';

    /**
     * @param Context $context
     * @param Registry $registry
     * @param SentPaymentReminderInterfaceFactory $sentpaymentreminderDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param ResourceModel\SentPaymentReminder $resource
     * @param Collection $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        SentPaymentReminderInterfaceFactory $sentpaymentreminderDataFactory,
        protected DataObjectHelper $dataObjectHelper,
        ResourceModel\SentPaymentReminder $resource,
        Collection $resourceCollection,
        array $data = [],
    ) {
        $this->sentpaymentreminderDataFactory = $sentpaymentreminderDataFactory;
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
            SentPaymentReminderInterface::class,
        );

        return $sentpaymentreminderDataObject;
    }
}
