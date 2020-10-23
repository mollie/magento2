<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

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
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Payment\Api\Data\SentPaymentReminderInterface;
use Mollie\Payment\Api\Data\SentPaymentReminderInterfaceFactory;
use Mollie\Payment\Api\Data\SentPaymentReminderSearchResultsInterfaceFactory;
use Mollie\Payment\Api\SentPaymentReminderRepositoryInterface;
use Mollie\Payment\Model\ResourceModel\SentPaymentReminder as ResourceSentPaymentReminder;
use Mollie\Payment\Model\ResourceModel\SentPaymentReminder\CollectionFactory as SentPaymentReminderCollectionFactory;

class SentPaymentReminderRepository implements SentPaymentReminderRepositoryInterface
{
    /**
     * @var ResourceSentPaymentReminder
     */
    protected $resource;

    /**
     * @var SentPaymentReminderFactory
     */
    protected $sentPaymentReminderFactory;

    /**
     * @var SentPaymentReminderCollectionFactory
     */
    protected $sentPaymentReminderCollectionFactory;

    /**
     * @var SentPaymentReminderSearchResultsInterfaceFactory
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
     * @var SentPaymentReminderInterfaceFactory
     */
    protected $dataSentPaymentReminderFactory;

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

    public function __construct(
        ResourceSentPaymentReminder $resource,
        SentPaymentReminderFactory $sentPaymentReminderFactory,
        SentPaymentReminderInterfaceFactory $dataSentPaymentReminderFactory,
        SentPaymentReminderCollectionFactory $sentPaymentReminderCollectionFactory,
        SentPaymentReminderSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->resource = $resource;
        $this->sentPaymentReminderFactory = $sentPaymentReminderFactory;
        $this->sentPaymentReminderCollectionFactory = $sentPaymentReminderCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataSentPaymentReminderFactory = $dataSentPaymentReminderFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
        $this->collectionProcessor = $collectionProcessor;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function save(SentPaymentReminderInterface $sentPaymentReminder)
    {
        /* if (empty($sentPaymentReminder->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $sentPaymentReminder->setStoreId($storeId);
        } */

        $sentPaymentReminderData = $this->extensibleDataObjectConverter->toNestedArray(
            $sentPaymentReminder,
            [],
            SentPaymentReminderInterface::class
        );

        $sentPaymentReminderModel = $this->sentPaymentReminderFactory->create()->setData($sentPaymentReminderData);

        try {
            $this->resource->save($sentPaymentReminderModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the sentPaymentReminder: %1',
                $exception->getMessage()
            ));
        }
        return $sentPaymentReminderModel->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function get(int $id)
    {
        $sentPaymentReminder = $this->sentPaymentReminderFactory->create();
        $this->resource->load($sentPaymentReminder, $id);
        if (!$sentPaymentReminder->getId()) {
            throw new NoSuchEntityException(__('SentPaymentReminder with id "%1" does not exist.', $id));
        }
        return $sentPaymentReminder->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getByOrderId(int $id)
    {
        $sentPaymentReminder = $this->sentPaymentReminderFactory->create();
        $this->resource->load($sentPaymentReminder, $id, 'order_id');
        if (!$sentPaymentReminder->getId()) {
            throw new NoSuchEntityException(__('SentPaymentReminder with id "%1" does not exist.', $id));
        }
        return $sentPaymentReminder->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getList(SearchCriteriaInterface $criteria)
    {
        $collection = $this->sentPaymentReminderCollectionFactory->create();

        $this->extensionAttributesJoinProcessor->process(
            $collection,
            SentPaymentReminderInterface::class
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
    public function delete(SentPaymentReminderInterface $sentPaymentReminder)
    {
        try {
            $sentPaymentReminderModel = $this->sentPaymentReminderFactory->create();
            $this->resource->load($sentPaymentReminderModel, $sentPaymentReminder->getEntityId());
            $this->resource->delete($sentPaymentReminderModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the SentPaymentReminder: %1',
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
