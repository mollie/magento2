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
use Mollie\Payment\Api\Data\PendingPaymentReminderInterface;
use Mollie\Payment\Api\Data\PendingPaymentReminderInterfaceFactory;
use Mollie\Payment\Api\Data\PendingPaymentReminderSearchResultsInterfaceFactory;
use Mollie\Payment\Api\PendingPaymentReminderRepositoryInterface;
use Mollie\Payment\Model\ResourceModel\PendingPaymentReminder as ResourcePendingPaymentReminder;
use Mollie\Payment\Model\ResourceModel\PendingPaymentReminder\CollectionFactory as PendingPaymentReminderCollectionFactory;

class PendingPaymentReminderRepository implements PendingPaymentReminderRepositoryInterface
{
    /**
     * @var ResourcePendingPaymentReminder
     */
    protected $resource;

    /**
     * @var PendingPaymentReminderFactory
     */
    protected $pendingPaymentReminderFactory;

    /**
     * @var PendingPaymentReminderCollectionFactory
     */
    protected $pendingPaymentReminderCollectionFactory;

    /**
     * @var PendingPaymentReminderSearchResultsInterfaceFactory
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
     * @var PendingPaymentReminderInterfaceFactory
     */
    protected $dataPendingPaymentReminderFactory;

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
        ResourcePendingPaymentReminder $resource,
        PendingPaymentReminderFactory $pendingPaymentReminderFactory,
        PendingPaymentReminderInterfaceFactory $dataPendingPaymentReminderFactory,
        PendingPaymentReminderCollectionFactory $pendingPaymentReminderCollectionFactory,
        PendingPaymentReminderSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        StoreManagerInterface $storeManager,
        CollectionProcessorInterface $collectionProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->resource = $resource;
        $this->pendingPaymentReminderFactory = $pendingPaymentReminderFactory;
        $this->pendingPaymentReminderCollectionFactory = $pendingPaymentReminderCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataPendingPaymentReminderFactory = $dataPendingPaymentReminderFactory;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->storeManager = $storeManager;
        $this->collectionProcessor = $collectionProcessor;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
    }

    /**
     * {@inheritdoc}
     */
    public function save(PendingPaymentReminderInterface $pendingPaymentReminder)
    {
        /* if (empty($pendingPaymentReminder->getStoreId())) {
            $storeId = $this->storeManager->getStore()->getId();
            $pendingPaymentReminder->setStoreId($storeId);
        } */

        $pendingPaymentReminderData = $this->extensibleDataObjectConverter->toNestedArray(
            $pendingPaymentReminder,
            [],
            PendingPaymentReminderInterface::class
        );

        $pendingPaymentReminderModel = $this->pendingPaymentReminderFactory->create()->setData($pendingPaymentReminderData);

        try {
            $this->resource->save($pendingPaymentReminderModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the pendingPaymentReminder: %1',
                $exception->getMessage()
            ));
        }
        return $pendingPaymentReminderModel->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function get(int $id)
    {
        $pendingPaymentReminder = $this->pendingPaymentReminderFactory->create();
        $this->resource->load($pendingPaymentReminder, $id);
        if (!$pendingPaymentReminder->getId()) {
            throw new NoSuchEntityException(__('PendingPaymentReminder with id "%1" does not exist.', $id));
        }
        return $pendingPaymentReminder->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getByOrderId(int $id)
    {
        $pendingPaymentReminder = $this->pendingPaymentReminderFactory->create();
        $this->resource->load($pendingPaymentReminder, $id, 'order_id');
        if (!$pendingPaymentReminder->getId()) {
            throw new NoSuchEntityException(__('PendingPaymentReminder with id "%1" does not exist.', $id));
        }
        return $pendingPaymentReminder->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getList(SearchCriteriaInterface $criteria)
    {
        $collection = $this->pendingPaymentReminderCollectionFactory->create();

        $this->extensionAttributesJoinProcessor->process(
            $collection,
            PendingPaymentReminderInterface::class
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
    public function delete(PendingPaymentReminderInterface $pendingPaymentReminder)
    {
        try {
            $pendingPaymentReminderModel = $this->pendingPaymentReminderFactory->create();
            $this->resource->load($pendingPaymentReminderModel, $pendingPaymentReminder->getEntityId());
            $this->resource->delete($pendingPaymentReminderModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the PendingPaymentReminder: %1',
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

    /**
     * {@inheritDoc}
     */
    public function deleteByOrderId(int $id)
    {
        return $this->delete($this->getByOrderId($id));
    }
}
