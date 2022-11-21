<?php declare(strict_types=1);

namespace Mollie\Payment\Model;

use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Mollie\Payment\Api\Data\ApiKeyFallbackInterface;
use Mollie\Payment\Api\Data\ApiKeyFallbackInterfaceFactory;
use Mollie\Payment\Api\Data\ApiKeyFallbackSearchResultsInterfaceFactory;
use Mollie\Payment\Api\ApiKeyFallbackRepositoryInterface;
use Mollie\Payment\Model\ResourceModel\ApiKeyFallback as ResourceApiKeyFallback;
use Mollie\Payment\Model\ResourceModel\ApiKeyFallback\CollectionFactory as ApiKeyFallbackCollectionFactory;

class ApiKeyFallbackRepository implements ApiKeyFallbackRepositoryInterface
{
    /**
     * @var ResourceApiKeyFallback
     */
    protected $resource;

    /**
     * @var ApiKeyFallbackFactory
     */
    protected $apiKeyFallbackFactory;

    /**
     * @var ApiKeyFallbackCollectionFactory
     */
    protected $apiKeyFallbackCollectionFactory;

    /**
     * @var ApiKeyFallbackSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    /**
     * @var DataObjectHelper
     */
    protected $dataObjectHelper;

    /**
     * @var DataObjectProcessor
     */
    protected $dataObjectProcessor;

    /**
     * @var ApiKeyFallbackInterfaceFactory
     */
    protected $dataApiKeyFallbackFactory;

    /**
     * @var JoinProcessorInterface
     */
    protected $extensionAttributesJoinProcessor;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var ExtensibleDataObjectConverter
     */
    protected $extensibleDataObjectConverter;

    public function __construct(
        ResourceApiKeyFallback $resource,
        ApiKeyFallbackFactory $apiKeyFallbackFactory,
        ApiKeyFallbackInterfaceFactory $dataApiKeyFallbackFactory,
        ApiKeyFallbackCollectionFactory $apiKeyFallbackCollectionFactory,
        ApiKeyFallbackSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->resource = $resource;
        $this->apiKeyFallbackFactory = $apiKeyFallbackFactory;
        $this->apiKeyFallbackCollectionFactory = $apiKeyFallbackCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataApiKeyFallbackFactory = $dataApiKeyFallbackFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->collectionProcessor = $collectionProcessor;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function save(ApiKeyFallbackInterface $apiKeyFallback)
    {
        $apiKeyFallbackData = $this->extensibleDataObjectConverter->toNestedArray(
            $apiKeyFallback,
            [],
            ApiKeyFallbackInterface::class
        );

        $apiKeyFallbackModel = $this->apiKeyFallbackFactory->create()->setData($apiKeyFallbackData);

        try {
            $this->resource->save($apiKeyFallbackModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the apiKeyFallback: %1',
                $exception->getMessage()
            ));
        }
        return $apiKeyFallbackModel->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function get(int $id)
    {
        $apiKeyFallback = $this->apiKeyFallbackFactory->create();
        $this->resource->load($apiKeyFallback, $id);
        if (!$apiKeyFallback->getId()) {
            throw new NoSuchEntityException(__('ApiKeyFallback with id "%1" does not exist.', $id));
        }
        return $apiKeyFallback->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getList(SearchCriteriaInterface $criteria)
    {
        $collection = $this->apiKeyFallbackCollectionFactory->create();

        $this->extensionAttributesJoinProcessor->process(
            $collection,
            ApiKeyFallbackInterface::class
        );

        $this->collectionProcessor->process($criteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $items = [];
        foreach ($collection as $model) {
            $items[] = $model->getDataModel();
        }

        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(ApiKeyFallbackInterface $apiKeyFallback)
    {
        try {
            $apiKeyFallbackModel = $this->apiKeyFallbackFactory->create();
            $this->resource->load($apiKeyFallbackModel, $apiKeyFallback->getEntityId());
            $this->resource->delete($apiKeyFallbackModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the ApiKeyFallback: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById(int $id)
    {
        return $this->delete($this->get($id));
    }
}
