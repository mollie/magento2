<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Payment\Api\Data\MollieCustomerInterface;
use Mollie\Payment\Api\Data\MollieCustomerInterfaceFactory;
use Mollie\Payment\Api\MollieCustomerRepositoryInterface;
use Mollie\Payment\Model\ResourceModel\MollieCustomer as ResourceCustomer;
use Mollie\Payment\Model\ResourceModel\MollieCustomer\Collection as CustomerCollection;
use Mollie\Payment\Model\ResourceModel\MollieCustomer\CollectionFactory as CustomerCollectionFactory;

class MollieCustomerRepository implements MollieCustomerRepositoryInterface
{
    /**
     * @var ResourceCustomer
     */
    protected $resource;

    /**
     * @var MollieCustomerFactory
     */
    protected $mollieCustomerFactory;

    /**
     * @var CustomerCollectionFactory
     */
    protected $customerCollectionFactory;

    /**
     * @var SearchResultsInterface
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
     * @var MollieCustomerInterfaceFactory
     */
    protected $dataCustomerFactory;

    /**
     * @var JoinProcessorInterface
     */
    protected $extensionAttributesJoinProcessor;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var ExtensibleDataObjectConverter
     */
    protected $extensibleDataObjectConverter;
    /**
     * @var SearchCriteriaBuilderFactory
     */
    private $criteriaBuilderFactory;

    public function __construct(
        ResourceCustomer $resource,
        MollieCustomerFactory $mollieCustomerFactory,
        MollieCustomerInterfaceFactory $dataCustomerFactory,
        CustomerCollectionFactory $customerCollectionFactory,
        SearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        SearchCriteriaBuilderFactory $criteriaBuilderFactory
    ) {
        $this->resource = $resource;
        $this->mollieCustomerFactory = $mollieCustomerFactory;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataCustomerFactory = $dataCustomerFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
        $this->collectionProcessor = $collectionProcessor;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
        $this->criteriaBuilderFactory = $criteriaBuilderFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function save(
        MollieCustomerInterface $customer
    ) {
        $customerData = $this->extensibleDataObjectConverter->toNestedArray(
            $customer,
            [],
            MollieCustomerInterface::class
        );

        $customerModel = $this->mollieCustomerFactory->create()->setData($customerData);

        try {
            $this->resource->save($customerModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the Mollie customer: %1',
                $exception->getMessage()
            ));
        }
        return $customerModel->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function get($customerId)
    {
        $customer = $this->mollieCustomerFactory->create();
        $this->resource->load($customer, $customerId);

        if (!$customer->getId()) {
            throw new NoSuchEntityException(__('Customer with id "%1" does not exist.', $customerId));
        }

        return $customer->getDataModel();
    }

    /**
     * {@inheritDoc}
     */
    public function getByMollieCustomerId(string $customerId)
    {
        $mollieCustomer = $this->mollieCustomerFactory->create();
        $this->resource->load($mollieCustomer, $customerId, 'mollie_customer_id');

        if (!$mollieCustomer->getId()) {
            return null;
        }

        return $mollieCustomer->getDataModel();
    }

    /**
     * {@inheritDoc}
     */
    public function getByCustomer(CustomerInterface $customer)
    {
        $mollieCustomer = $this->mollieCustomerFactory->create();
        $this->resource->load($mollieCustomer, $customer->getId(), 'customer_id');

        if (!$mollieCustomer->getId()) {
            return null;
        }

        return $mollieCustomer->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getList(SearchCriteriaInterface $criteria)
    {
        /** @var CustomerCollection $collection */
        $collection = $this->customerCollectionFactory->create();

        $this->extensionAttributesJoinProcessor->process(
            $collection,
            MollieCustomerInterface::class
        );

        $this->collectionProcessor->process($criteria, $collection);

        /** @var SearchResultsInterface $searchResults */
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
    public function delete(
        MollieCustomerInterface $customer
    ) {
        try {
            $customerModel = $this->mollieCustomerFactory->create();
            $this->resource->load($customerModel, $customer->getCustomerId());
            $this->resource->delete($customerModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the Customer: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($customerId)
    {
        return $this->delete($this->get($customerId));
    }
}
