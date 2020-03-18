<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model;

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
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Payment\Api\Data\PaymentTokenInterface;
use Mollie\Payment\Api\Data\PaymentTokenInterfaceFactory;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\Model\ResourceModel\PaymentToken as ResourcePaymentToken;
use Mollie\Payment\Model\ResourceModel\PaymentToken\Collection as PaymentTokenCollection;
use Mollie\Payment\Model\ResourceModel\PaymentToken\CollectionFactory as PaymentTokenCollectionFactory;

class PaymentTokenRepository implements PaymentTokenRepositoryInterface
{
    /**
     * @var ResourcePaymentToken
     */
    protected $resource;

    /**
     * @var PaymentTokenFactory
     */
    protected $paymentTokenFactory;

    /**
     * @var PaymentTokenCollectionFactory
     */
    protected $paymentTokenCollectionFactory;

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
     * @var PaymentTokenInterfaceFactory
     */
    protected $dataPaymentTokenFactory;

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
        ResourcePaymentToken $resource,
        PaymentTokenFactory $paymentTokenFactory,
        PaymentTokenInterfaceFactory $dataPaymentTokenFactory,
        PaymentTokenCollectionFactory $paymentTokenCollectionFactory,
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
        $this->paymentTokenFactory = $paymentTokenFactory;
        $this->paymentTokenCollectionFactory = $paymentTokenCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataPaymentTokenFactory = $dataPaymentTokenFactory;
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
        PaymentTokenInterface $paymentToken
    ) {
        $paymentTokenData = $this->extensibleDataObjectConverter->toNestedArray(
            $paymentToken,
            [],
            PaymentTokenInterface::class
        );

        $paymentTokenModel = $this->paymentTokenFactory->create()->setData($paymentTokenData);

        try {
            $this->resource->save($paymentTokenModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__(
                'Could not save the paymentToken: %1',
                $exception->getMessage()
            ));
        }
        return $paymentTokenModel->getDataModel();
    }

    /**
     * {@inheritdoc}
     */
    public function get($paymentTokenId)
    {
        $paymentToken = $this->paymentTokenFactory->create();
        $this->resource->load($paymentToken, $paymentTokenId);

        if (!$paymentToken->getId()) {
            throw new NoSuchEntityException(__('PaymentToken with id "%1" does not exist.', $paymentTokenId));
        }

        return $paymentToken->getDataModel();
    }

    /**
     * {@inheritDoc}
     */
    public function getByToken($token)
    {
        $paymentToken = $this->paymentTokenFactory->create();
        $this->resource->load($paymentToken, $token, 'token');

        if (!$paymentToken->getId()) {
            return null;
        }

        return $paymentToken->getDataModel();
    }

    public function getByOrder(OrderInterface $order)
    {
        $paymentToken = $this->paymentTokenFactory->create();
        $this->resource->load($paymentToken, $order->getEntityId(), 'order_id');

        if (!$paymentToken->getId()) {
            return null;
        }

        return $paymentToken->getDataModel();
    }

    /**
     * {@inheritDoc}
     */
    public function getByCart(CartInterface $cart)
    {
        $criteria = $this->criteriaBuilderFactory->create();
        $criteria->addFilter('cart_id', $cart->getId());

        return $this->getList($criteria->create());
    }

    /**
     * {@inheritdoc}
     */
    public function getList(SearchCriteriaInterface $criteria)
    {
        /** @var PaymentTokenCollection $collection */
        $collection = $this->paymentTokenCollectionFactory->create();

        $this->extensionAttributesJoinProcessor->process(
            $collection,
            PaymentTokenInterface::class
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
        PaymentTokenInterface $paymentToken
    ) {
        try {
            $paymentTokenModel = $this->paymentTokenFactory->create();
            $this->resource->load($paymentTokenModel, $paymentToken->getPaymenttokenId());
            $this->resource->delete($paymentTokenModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__(
                'Could not delete the PaymentToken: %1',
                $exception->getMessage()
            ));
        }
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function deleteById($paymentTokenId)
    {
        return $this->delete($this->get($paymentTokenId));
    }
}
