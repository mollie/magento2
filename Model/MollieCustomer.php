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
use Mollie\Payment\Api\Data\MollieCustomerInterface;
use Mollie\Payment\Api\Data\MollieCustomerInterfaceFactory;
use Mollie\Payment\Model\ResourceModel\MollieCustomer\Collection;

class MollieCustomer extends AbstractModel
{
    /**
     * @var string
     */
    protected $_eventPrefix = 'mollie_payment_customer';

    /**
     * @param Context $context
     * @param Registry $registry
     * @param MollieCustomerInterfaceFactory $customerDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param ResourceModel\MollieCustomer $resource
     * @param Collection $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        protected MollieCustomerInterfaceFactory $customerDataFactory,
        protected DataObjectHelper $dataObjectHelper,
        ResourceModel\MollieCustomer $resource,
        Collection $resourceCollection,
        array $data = [],
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Retrieve customer model with customer data
     * @return MollieCustomerInterface
     */
    public function getDataModel()
    {
        $customerData = $this->getData();

        $customerDataObject = $this->customerDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $customerDataObject,
            $customerData,
            MollieCustomerInterface::class,
        );

        return $customerDataObject;
    }
}
