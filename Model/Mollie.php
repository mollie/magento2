<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Config\ValueHandlerPoolInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Gateway\Validator\ValidatorPoolInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Payment\Model\Method\Adapter;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderFactory;
use Mollie\Api\MollieApiClient;
use Mollie\Payment\Api\Data\TransactionToOrderInterface;
use Mollie\Payment\Api\TransactionToOrderRepositoryInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Exceptions\PaymentAborted;
use Mollie\Payment\Helper\General as MollieHelper;
use Mollie\Payment\Model\Client\Orders as OrdersApi;
use Mollie\Payment\Model\Client\Orders\ProcessTransaction;
use Mollie\Payment\Model\Client\Payments as PaymentsApi;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Service\Mollie\GetApiMethod;
use Mollie\Payment\Service\Mollie\LogException;
use Mollie\Payment\Service\OrderLockService;
use Mollie\Payment\Service\Mollie\Timeout;
use Mollie\Payment\Service\Mollie\Wrapper\MollieApiClientFallbackWrapper;
use Psr\Log\LoggerInterface;

/**
 * Class Mollie
 *
 * @package Mollie\Payment\Model
 */
class Mollie extends Adapter
{
    const CODE = 'mollie';

    /**
     * @var Registry
     */
    private $registry;
    /**
     * @var ManagerInterface
     */
    private $eventManager;
    /**
     * @var Config
     */
    protected $config;
    /**
     * @var MollieHelper
     */
    private $mollieHelper;
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    /**
     * @var OrderRepository
     */
    private $orderRepository;
    /**
     * @var OrderFactory
     */
    private $orderFactory;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var OrdersApi
     */
    private $ordersApi;
    /**
     * @var PaymentsApi
     */
    private $paymentsApi;
    /**
     * @var AssetRepository
     */
    private $assetRepository;
    /**
     * @var Timeout
     */
    private $timeout;
    /**
     * @var ProcessTransaction
     */
    private $ordersProcessTransaction;

    /**
     * @var OrderLockService
     */
    private $orderLockService;

    /**
     * @var \Mollie\Payment\Service\Mollie\MollieApiClient
     */
    private $mollieApiClient;

    /**
     * @var TransactionToOrderRepositoryInterface
     */
    private $transactionToOrderRepository;
    /**
     * @var GetApiMethod
     */
    private $getApiMethod;
    /**
     * @var LogException
     */
    private $logException;

    public function __construct(
        ManagerInterface $eventManager,
        ValueHandlerPoolInterface $valueHandlerPool,
        PaymentDataObjectFactory $paymentDataObjectFactory,
        Registry $registry,
        OrderRepository $orderRepository,
        OrderFactory $orderFactory,
        OrdersApi $ordersApi,
        PaymentsApi $paymentsApi,
        MollieHelper $mollieHelper,
        CheckoutSession $checkoutSession,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AssetRepository $assetRepository,
        Config $config,
        Timeout $timeout,
        ProcessTransaction $ordersProcessTransaction,
        OrderLockService $orderLockService,
        \Mollie\Payment\Service\Mollie\MollieApiClient $mollieApiClient,
        TransactionToOrderRepositoryInterface $transactionToOrderRepository,
        GetApiMethod $getApiMethod,
        LogException $logException,
        $formBlockType,
        $infoBlockType,
        ?CommandPoolInterface $commandPool = null,
        ?ValidatorPoolInterface $validatorPool = null,
        ?CommandManagerInterface $commandExecutor = null,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct(
            $eventManager,
            $valueHandlerPool,
            $paymentDataObjectFactory,
            static::CODE,
            $formBlockType,
            $infoBlockType,
            $commandPool,
            $validatorPool,
            $commandExecutor,
            $logger
        );

        $this->registry = $registry;
        $this->eventManager = $eventManager;
        $this->paymentsApi = $paymentsApi;
        $this->ordersApi = $ordersApi;
        $this->mollieHelper = $mollieHelper;
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->orderFactory = $orderFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->assetRepository = $assetRepository;
        $this->config = $config;
        $this->timeout = $timeout;
        $this->ordersProcessTransaction = $ordersProcessTransaction;
        $this->orderLockService = $orderLockService;
        $this->mollieApiClient = $mollieApiClient;
        $this->transactionToOrderRepository = $transactionToOrderRepository;
        $this->getApiMethod = $getApiMethod;
        $this->logException = $logException;
    }

    public function getCode()
    {
        return static::CODE;
    }

    /**
     * Extra checks for method availability
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     *
     * @return bool
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function isAvailable(?\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        if ($quote == null) {
            $quote = $this->checkoutSession->getQuote();
        }

        if (!$this->mollieHelper->isAvailable($quote->getStoreId())) {
            return false;
        }

        if ($quote->getIsMultiShipping() && !$this->config->isMultishippingEnabled()) {
            return false;
        }

        $activeMethods = $this->mollieHelper->getAllActiveMethods($quote->getStoreId());
        if ($this::CODE != 'mollie_methods_paymentlink' && !array_key_exists($this::CODE, $activeMethods)) {
            return false;
        }

        return parent::isAvailable($quote);
    }

    /**
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function initialize($paymentAction, $stateObject)
    {
        /** @var \Magento\Sales\Model\Order\Payment $payment */
        $payment = $this->getInfoInstance();

        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);

        $status = $this->mollieHelper->getStatusPending($order->getStoreId());
        $stateObject->setState(Order::STATE_NEW);
        $stateObject->setStatus($status);
        $stateObject->setIsNotified(false);
    }

    /**
     * @param Order|OrderInterface $order
     *
     * @return string|null|bool
     * @throws LocalizedException
     * @throws \Mollie\Api\Exceptions\ApiException
     */
    public function startTransaction(Order $order)
    {
        $this->eventManager->dispatch('mollie_start_transaction', ['order' => $order]);

        $storeId = $order->getStoreId();
        if (!$apiKey = $this->mollieHelper->getApiKey($storeId)) {
            return false;
        }

        return $this->orderLockService->execute($order, function (OrderInterface $order) use ($apiKey) {
            $mollieApi = $this->loadMollieApi($apiKey);
            $method = $this->getApiMethod->execute($order);

            // When clicking the back button from the hosted payment we need a way to verify if the order was paid or not.
            // If this is not the case, we restore the quote. This flag is used to determine if it was paid or not.
            $order->getPayment()->setAdditionalInformation('mollie_success', false);

            if ($method == 'order') {
                return $this->startTransactionUsingTheOrdersApi($order, $mollieApi);
            }

            return $this->timeout->retry( function () use ($order, $mollieApi) {
                return $this->paymentsApi->startTransaction($order, $mollieApi);
            });
        });
    }

    private function startTransactionUsingTheOrdersApi(OrderInterface $order, MollieApiClient $mollieApi)
    {
        try {
            return $this->timeout->retry( function () use ($order, $mollieApi) {
                return $this->ordersApi->startTransaction($order, $mollieApi);
            });
        } catch (\Exception $exception) {
            $this->logException->execute($exception);
        }

        $methodCode = $this->mollieHelper->getMethodCode($order);
        $methods = [
            'alma',
            'billie',
            'klarna',
            'klarnapaylater',
            'klarnapaynow',
            'klarnasliceit',
            'voucher',
            'riverty',
            'in3'
        ];

        if (in_array($methodCode, $methods) || $exception instanceof PaymentAborted) {
            throw new LocalizedException(__($exception->getMessage()));
        }

        // Retry the order using the "payment" method.
        return $this->timeout->retry( function () use ($order, $mollieApi) {
            return $this->paymentsApi->startTransaction($order, $mollieApi);
        });
    }

    /**
     * @param $apiKey
     *
     * @return MollieApiClient
     * @throws \Mollie\Api\Exceptions\ApiException
     * @throws LocalizedException
     */
    public function loadMollieApi($apiKey)
    {
        return $this->mollieApiClient->loadByApiKey($apiKey);
    }

    public function loadMollieApiWithFallbackWrapper($apiKey): MollieApiClientFallbackWrapper
    {
        return new MollieApiClientFallbackWrapper($this->loadMollieApi($apiKey));
    }

    /**
     * @param $storeId
     * @return MollieApiClient|null
     */
    public function getMollieApi($storeId = null)
    {
        $apiKey = $this->mollieHelper->getApiKey($storeId);

        try {
            return $this->loadMollieApi($apiKey);
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
            return null;
        }
    }

    /**
     * @param        $orderId
     * @param string $type
     * @param null   $paymentToken
     *
     * @return ProcessTransactionResponse
     * @throws LocalizedException
     * @throws \Mollie\Api\Exceptions\ApiException
     */
    public function processTransaction($orderId, $type = 'webhook', $paymentToken = null)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $this->orderRepository->get($orderId);

        return $this->processTransactionForOrder($order, $type, $paymentToken);
    }

    public function processTransactionForOrder(OrderInterface $order, $type = 'webhook', $paymentToken = null)
    {
        $this->eventManager->dispatch('mollie_process_transaction_start', ['order' => $order]);

        $transactionId = $order->getMollieTransactionId();
        if (empty($transactionId)) {
            $msg = ['error' => true, 'msg' => __('Transaction ID not found')];
            $this->mollieHelper->addTolog('error', $msg);
            return $msg;
        }

        $output = $this->orderLockService->execute($order, function (OrderInterface $order) use (
            $transactionId,
            $type,
            $paymentToken
        ) {
            if (substr($transactionId, 0, 4) == 'ord_') {
                $result = $this->ordersProcessTransaction->execute($order, $type)->toArray();
            } else {
                $mollieApi = $this->mollieApiClient->loadByStore($order->getStoreId());
                $result = $this->paymentsApi->processTransaction($order, $mollieApi, $type, $paymentToken);
            }

            $order->getPayment()->setAdditionalInformation('mollie_success', $result['success']);

            // Return the order and the result so we can use this outside this closure.
            return [
                'order' => $order,
                'result' => $result,
            ];
        });

        // Extract the contents of the closure.
        $order = $output['order'];
        $result = $output['result'];

        $this->eventManager->dispatch('mollie_process_transaction_end', ['order' => $order]);

        return $result;
    }

    public function orderHasUpdate($orderId)
    {
        $order = $this->orderRepository->get($orderId);

        $transactionId = $order->getMollieTransactionId();
        if (empty($transactionId)) {
            $msg = ['error' => true, 'msg' => __('Transaction ID not found')];
            $this->mollieHelper->addTolog('error', $msg);
            return $msg;
        }

        $mollieApi = $this->mollieApiClient->loadByStore($order->getStoreId());

        if (substr($transactionId, 0, 4) == 'ord_') {
            return $this->ordersApi->orderHasUpdate($order, $mollieApi);
        } else {
            return $this->paymentsApi->orderHasUpdate($order, $mollieApi);
        }
    }

    /**
     * @param DataObject $data
     *
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function assignData(DataObject $data)
    {
        parent::assignData($data);

        $additionalData = $data->getAdditionalData();
        if (isset($additionalData['applepay_payment_token'])) {
            $this->getInfoInstance()->setAdditionalInformation('applepay_payment_token', $additionalData['applepay_payment_token']);
        }

        if (isset($additionalData['card_token'])) {
            $this->getInfoInstance()->setAdditionalInformation('card_token', $additionalData['card_token']);
        }

        if (isset($additionalData['selected_issuer'])) {
            $this->getInfoInstance()->setAdditionalInformation('selected_issuer', $additionalData['selected_issuer']);
        }

        if (isset($additionalData['selected_terminal'])) {
            $this->getInfoInstance()->setAdditionalInformation('selected_terminal', $additionalData['selected_terminal']);
        }

        return $this;
    }

    /**
     * @param Order\Shipment       $shipment
     * @param Order\Shipment\Track $track
     * @param Order                $order
     *
     * @return OrdersApi
     * @throws LocalizedException
     */
    public function updateShipmentTrack(Order\Shipment $shipment, Order\Shipment\Track $track, Order $order)
    {
        return $this->ordersApi->updateShipmentTrack($shipment, $track, $order);
    }

    /**
     * @param Order $order
     *
     * @return OrdersApi
     * @throws LocalizedException
     */
    public function cancelOrder(Order $order)
    {
        return $this->ordersApi->cancelOrder($order);
    }

    /**
     * @param Order\Creditmemo $creditmemo
     * @param Order            $order
     *
     * @return $this
     * @throws LocalizedException
     */
    public function createOrderRefund(Order\Creditmemo $creditmemo, Order $order)
    {
        $this->ordersApi->createOrderRefund($creditmemo, $order);
        return $this;
    }

    /**
     * @param InfoInterface $payment
     * @param float                                $amount
     *
     * @return $this
     * @throws LocalizedException
     */
    public function refund(InfoInterface $payment, $amount): self
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();
        $storeId = $order->getStoreId();

        /**
         * Order Api does not use amount to refund, but refunds per itemLine
         * See SalesOrderCreditmemoSaveAfter Observer for logic.
         */
        $checkoutType = $this->mollieHelper->getCheckoutType($order);
        if ($checkoutType == 'order') {
            $this->registry->register('online_refund', true);
            return $this;
        }

        $transactionId = $order->getMollieTransactionId();
        if (empty($transactionId)) {
            $msg = ['error' => true, 'msg' => __('Transaction ID not found')];
            $this->mollieHelper->addTolog('error', $msg);
            return $this;
        }

        $apiKey = $this->mollieHelper->getApiKey($storeId);
        if (empty($apiKey)) {
            $msg = ['error' => true, 'msg' => __('Api key not found')];
            $this->mollieHelper->addTolog('error', $msg);
            return $this;
        }

        try {
            // The provided $amount is in the currency of the default store. If we are not using the base currency,
            // Get the order amount in the currency of the order.
            if (!$this->config->useBaseCurrency($order->getStoreId())) {
                $amount = $payment->getCreditMemo()->getGrandTotal();
            }

            $mollieApi = $this->loadMollieApi($apiKey);
            $payment = $mollieApi->payments->get($transactionId);
            $payment->refund([
                'amount' => [
                    'currency' => $order->getOrderCurrencyCode(),
                    'value'    => $this->mollieHelper->formatCurrencyValue($amount, $order->getOrderCurrencyCode())
                ]
            ]);
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
            throw new LocalizedException(__('Error: not possible to create an online refund: %1', $e->getMessage()));
        }

        return $this;
    }

    /**
     * Get order by TransactionId
     *
     * @param $transactionId
     *
     * @return mixed
     */
    public function getOrderIdByTransactionId($transactionId)
    {
        $orderId = $this->orderFactory->create()
            ->addFieldToFilter('mollie_transaction_id', $transactionId)
            ->getFirstItem()
            ->getId();

        if ($orderId) {
            return $orderId;
        } else {
            $this->mollieHelper->addTolog('error', __('No order found for transaction id %1', $transactionId));
            return false;
        }
    }

    /**
     * Get order(s) by TransactionId
     *
     * @param string $transactionId
     * @return array
     */
    public function getOrderIdsByTransactionId(string $transactionId): array
    {
        $this->searchCriteriaBuilder->addFilter('transaction_id', $transactionId);
        $orders = $this->transactionToOrderRepository->getList($this->searchCriteriaBuilder->create());

        if (!$orders->getTotalCount()) {
            $this->mollieHelper->addTolog('error', __('No order(s) found for transaction id %1', $transactionId));
            return [];
        }

        return array_map(function (TransactionToOrderInterface $transactionToOrder) {
            return $transactionToOrder->getOrderId();
        }, $orders->getItems());
    }

    /**
     * Get list of Issuers from API
     *
     * @param string $method
     * @param string $issuerListType
     * @param int $count for internal use only
     *
     * @return array|null
     */
    public function getIssuers(string $method, string $issuerListType, int $count = 0): ?array
    {
        $issuers = [];
        // iDeal 2.0 does not have issuers anymore.
        if ($issuerListType == 'none' || $method == 'mollie_methods_ideal') {
            return $issuers;
        }

        $mollieApi = $this->mollieApiClient->loadByStore();
        $methodCode = str_replace('mollie_methods_', '', $method);
        try {
            $issuers = $mollieApi->methods->get($methodCode, ['include' => 'issuers'])->issuers;

            // If the list can't be retrieved for some reason, try again.
            if (!$issuers && $count == 0) {
                $this->mollieHelper->addTolog(
                    'error',
                    'Retrieving method issuers gave an issue. Retrying.' . var_export($issuers, true)
                );

                return $this->getIssuers($method, $issuerListType, 1);
            }

            if (!$issuers) {
                return null;
            }
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
        }

        if ($this->mollieHelper->addQrOption() && $methodCode == 'ideal') {
            $issuers[] = [
                'resource' => 'issuer',
                'id'       => '',
                'name'     => __('QR Code'),
                'image'    => [
                    'size2x' => $this->assetRepository->getUrlWithParams(
                        'Mollie_Payment::images/qr-select.svg',
                        ['area'=>'frontend']
                    ),
                    'svg' => $this->assetRepository->getUrlWithParams(
                        'Mollie_Payment::images/qr-select.svg',
                        ['area'=>'frontend']
                    ),
                ]
            ];
        }

        // Sort the list by name
        uasort($issuers, function($a, $b) {
            $a = (array)$a;
            $b = (array)$b;

            return strcmp(strtolower($a['name']), strtolower($b['name']));
        });

        if ($issuers && $issuerListType == 'dropdown') {
            array_unshift($issuers, [
                'resource' => 'issuer',
                'id'       => '',
                'name'     => __('-- Please Select --')
            ]);
        }

        return array_values($issuers);
    }

    /**
     * Get list payment methods by Store ID.
     * Used in Observer/ConfigObserver to validate payment methods.
     *
     * @param $storeId
     *
     * @return bool|\Mollie\Api\Resources\MethodCollection
     * @throws \Mollie\Api\Exceptions\ApiException
     * @throws LocalizedException
     */
    public function getPaymentMethods($storeId)
    {
        $apiKey = $this->mollieHelper->getApiKey($storeId);

        if (empty($apiKey)) {
            return false;
        }

        $mollieApi = $this->loadMollieApi($apiKey);

        return $mollieApi->methods->allAvailable();
    }
}
