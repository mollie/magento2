<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model;

use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Mollie\Payment\Api\Data\PaymentTokenInterface;
use Mollie\Payment\Api\Data\PaymentTokenInterfaceFactory;
use Magento\Framework\Api\DataObjectHelper;
use Mollie\Payment\Model\ResourceModel\PaymentToken\Collection;

class PaymentToken extends AbstractModel
{
    /**
     * @var PaymentTokenInterfaceFactory
     */
    protected $paymenttokenDataFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var string
     */
    protected $_eventPrefix = 'mollie_payment_paymenttoken';

    /**
     * @param Context $context
     * @param Registry $registry
     * @param PaymentTokenInterfaceFactory $paymenttokenDataFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param ResourceModel\PaymentToken $resource
     * @param Collection $resourceCollection
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        PaymentTokenInterfaceFactory $paymenttokenDataFactory,
        DataObjectHelper $dataObjectHelper,
        ResourceModel\PaymentToken $resource,
        Collection $resourceCollection,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->paymenttokenDataFactory = $paymenttokenDataFactory;
        $this->dataObjectHelper = $dataObjectHelper;
    }

    /**
     * Retrieve paymenttoken model with paymenttoken data
     * @return PaymentTokenInterface
     */
    public function getDataModel()
    {
        $paymenttokenData = $this->getData();

        $paymenttokenDataObject = $this->paymenttokenDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $paymenttokenDataObject,
            $paymenttokenData,
            PaymentTokenInterface::class
        );

        return $paymenttokenDataObject;
    }
}
