<?php declare(strict_types=1);

namespace Mollie\Payment\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Mollie\Payment\Api\Data\ApiKeyFallbackInterface;
use Mollie\Payment\Api\Data\ApiKeyFallbackInterfaceFactory;

class ApiKeyFallback extends AbstractModel
{
    /**
     * @var DataObjectHelper
     */
    private $dataObjectHelper;

    /**
     * @var ApiKeyFallbackInterfaceFactory
     */
    private $apiKeyFallbackDataFactory;

    public function __construct(
        Context $context,
        Registry $registry,
        DataObjectHelper $dataObjectHelper,
        ApiKeyFallbackInterfaceFactory $apiKeyFallbackDataFactory,
        ResourceModel\ApiKeyFallback $resource,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);

        $this->dataObjectHelper = $dataObjectHelper;
        $this->apiKeyFallbackDataFactory = $apiKeyFallbackDataFactory;
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
            ApiKeyFallbackInterfaceFactory::class
        );

        return $dataObject;
    }
}
