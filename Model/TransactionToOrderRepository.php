<?php declare(strict_types=1);

namespace Mollie\Payment\Model;

use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Mollie\Payment\Api\Data\TransactionToOrderInterface;
use Mollie\Payment\Api\Data\TransactionToOrderInterfaceFactory;
use Mollie\Payment\Api\Data\TransactionToOrderSearchResultsInterface;
use Mollie\Payment\Api\Data\TransactionToOrderSearchResultsInterfaceFactory;
use Mollie\Payment\Api\TransactionToOrderRepositoryInterface;
use Mollie\Payment\Model\ResourceModel\TransactionToOrder as ResourceTransactionToOrder;
use Mollie\Payment\Model\ResourceModel\TransactionToOrder\CollectionFactory as TransactionToOrderCollectionFactory;

class TransactionToOrderRepository implements TransactionToOrderRepositoryInterface
{
    /**
     * @var ResourceTransactionToOrder
     */
    private $resource;

    /**
     * @var TransactionToOrderFactory
     */
    private $transactionToOrderFactory;

    /**
     * @var TransactionToOrderCollectionFactory
     */
    private $transactionToOrderCollectionFactory;

    /**
     * @var CollectionProcessorInterface
     */
    private $collectionProcessor;

    /**
     * @var TransactionToOrderSearchResultsInterfaceFactory
     */
    private $searchResultsFactory;

    /**
     * @var JoinProcessorInterface
     */
    private $extensionAttributesJoinProcessor;

    public function __construct(
        ResourceTransactionToOrder $resource,
        TransactionToOrderFactory $transactionToOrderFactory,
        TransactionToOrderCollectionFactory $transactionToOrderCollectionFactory,
        CollectionProcessorInterface $collectionProcessor,
        TransactionToOrderSearchResultsInterfaceFactory $searchResultsFactory,
        JoinProcessorInterface $extensionAttributesJoinProcessor
    ) {
        $this->resource = $resource;
        $this->transactionToOrderFactory = $transactionToOrderFactory;
        $this->transactionToOrderCollectionFactory = $transactionToOrderCollectionFactory;
        $this->collectionProcessor = $collectionProcessor;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
    }

    /**
     * {@inheritdoc}
     */
    public function save(TransactionToOrderInterface $entity): TransactionToOrderInterface
    {
        try {
            $this->resource->save($entity);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the transactionToOrder: %1',
                $exception->getMessage()
            ));
        }
        return $entity;
    }

    /**
     * {@inheritdoc}
     */
    public function get(int $id): TransactionToOrderInterface
    {
        $transactionToOrder = $this->transactionToOrderFactory->create();
        $this->resource->load($transactionToOrder, $id);
        if (!$transactionToOrder->getId()) {
            throw new NoSuchEntityException(__('TransactionToOrder with id "%1" does not exist.', $id));
        }
        return $transactionToOrder->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function getList(SearchCriteriaInterface $criteria): TransactionToOrderSearchResultsInterface
    {
        $collection = $this->transactionToOrderCollectionFactory->create();

        $this->extensionAttributesJoinProcessor->process(
            $collection,
            TransactionToOrderInterface::class
        );

        $this->collectionProcessor->process($criteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $items = [];
        foreach ($collection as $model) {
            $items[] = $model;
        }

        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(TransactionToOrderInterface $entity): bool
    {
        try {
            $transactionToOrderModel = $this->transactionToOrderFactory->create();
            $this->resource->load($transactionToOrderModel, $entity->getEntityId());
            $this->resource->delete($transactionToOrderModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the TransactionToOrder: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById(int $id): bool
    {
        return $this->delete($this->get($id));
    }
}
