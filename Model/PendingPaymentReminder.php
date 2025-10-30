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
use Mollie\Payment\Api\Data\PendingPaymentReminderInterface;
use Mollie\Payment\Api\Data\PendingPaymentReminderInterfaceFactory;
use Mollie\Payment\Model\ResourceModel\PendingPaymentReminder\Collection;

class PendingPaymentReminder extends AbstractModel
{
    protected $_eventPrefix = 'mollie_sent_payment_reminder';

    /**
     * @param Context $context
     * @param Registry $registry
     * @param PendingPaymentReminderInterfaceFactory $pendingpaymentreminderDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param ResourceModel\PendingPaymentReminder $resource
     * @param Collection $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        protected PendingPaymentReminderInterfaceFactory $pendingpaymentreminderDataFactory,
        protected DataObjectHelper $dataObjectHelper,
        ResourceModel\PendingPaymentReminder $resource,
        Collection $resourceCollection,
        array $data = [],
    ) {
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
            PendingPaymentReminderInterface::class,
        );

        return $pendingpaymentreminderDataObject;
    }
}
