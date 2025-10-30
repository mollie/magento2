<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Mollie\Payment\Api\Data\ApiKeyFallbackInterface;
use Mollie\Payment\Api\Data\ApiKeyFallbackInterfaceFactory;

class ApiKeyFallback extends AbstractModel
{
    public function __construct(
        Context $context,
        Registry $registry,
        private DataObjectHelper $dataObjectHelper,
        private ApiKeyFallbackInterfaceFactory $apiKeyFallbackDataFactory,
        ResourceModel\ApiKeyFallback $resource,
        ?AbstractDb $resourceCollection = null,
        array $data = [],
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * @return ApiKeyFallbackInterface
     */
    public function getDataModel(): ApiKeyFallbackInterface
    {
        $data = $this->getData();

        $dataObject = $this->apiKeyFallbackDataFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $dataObject,
            $data,
            ApiKeyFallbackInterfaceFactory::class,
        );

        return $dataObject;
    }
}
