<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model;

use Exception;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Mollie\Payment\Api\Data\SentPaymentReminderInterface;
use Mollie\Payment\Api\Data\SentPaymentReminderSearchResultsInterfaceFactory;
use Mollie\Payment\Api\SentPaymentReminderRepositoryInterface;
use Mollie\Payment\Model\ResourceModel\SentPaymentReminder as ResourceSentPaymentReminder;
use Mollie\Payment\Model\ResourceModel\SentPaymentReminder\CollectionFactory as SentPaymentReminderCollectionFactory;

class SentPaymentReminderRepository implements SentPaymentReminderRepositoryInterface
{
    /**
     * @var SentPaymentReminderFactory
     */
    protected $sentPaymentReminderFactory;

    /**
     * @var SentPaymentReminderSearchResultsInterfaceFactory
     */
    protected $searchResultsFactory;

    public function __construct(
        protected ResourceSentPaymentReminder $resource,
        SentPaymentReminderFactory $sentPaymentReminderFactory,
        protected SentPaymentReminderCollectionFactory $sentPaymentReminderCollectionFactory,
        SentPaymentReminderSearchResultsInterfaceFactory $searchResultsFactory,
        private CollectionProcessorInterface $collectionProcessor,
        protected JoinProcessorInterface $extensionAttributesJoinProcessor,
        protected ExtensibleDataObjectConverter $extensibleDataObjectConverter,
    ) {
        $this->sentPaymentReminderFactory = $sentPaymentReminderFactory;
        $this->searchResultsFactory = $searchResultsFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function save(SentPaymentReminderInterface $sentPaymentReminder)
    {
        $sentPaymentReminderData = $this->extensibleDataObjectConverter->toNestedArray(
            $sentPaymentReminder,
            [],
            SentPaymentReminderInterface::class,
        );

        $sentPaymentReminderModel = $this->sentPaymentReminderFactory->create()->setData($sentPaymentReminderData);

        try {
            $this->resource->save($sentPaymentReminderModel);
        } catch (Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the sentPaymentReminder: %1',
                $exception->getMessage(),
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
            SentPaymentReminderInterface::class,
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
    public function delete(SentPaymentReminderInterface $sentPaymentReminder): bool
    {
        try {
            $model = $this->sentPaymentReminderFactory->create();
            $model->setId($sentPaymentReminder->getEntityId());
            $this->resource->delete($model);
        } catch (Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the SentPaymentReminder: %1',
                $exception->getMessage(),
            ));
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById(int $id): bool
    {
        $model = $this->sentPaymentReminderFactory->create();
        $this->resource->load($model, $id);
        if (!$model->getId()) {
            throw new NoSuchEntityException(__('SentPaymentReminder with id "%1" does not exist.', $id));
        }
        try {
            $this->resource->delete($model);
        } catch (Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the SentPaymentReminder: %1',
                $exception->getMessage(),
            ));
        }

        return true;
    }
}
