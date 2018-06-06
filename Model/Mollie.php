<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model;

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\Method\Logger;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\OrderRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\DataObject;
use Mollie\Payment\Helper\General as MollieHelper;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class Mollie
 *
 * @package Mollie\Payment\Model
 */
class Mollie extends AbstractMethod
{

    /**
     * Enable Initialize
     *
     * @var bool
     */
    protected $_isInitializeNeeded = true;
    /**
     * Enable Gateway
     *
     * @var bool
     */
    protected $_isGateway = true;
    /**
     * Enable Refund
     *
     * @var bool
     */
    protected $_canRefund = true;
    /**
     * Enable Partial Refund
     *
     * @var bool
     */
    protected $_canRefundInvoicePartial = true;

    /**
     * @var array
     */
    private $issuers = [];
    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var MollieHelper
     */
    private $mollieHelper;
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;
    /**
     * @var Order
     */
    private $order;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var OrderSender
     */
    private $orderSender;
    /**
     * @var InvoiceSender
     */
    private $invoiceSender;
    /**
     * @var OrderRepository
     */
    private $orderRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * Mollie constructor.
     *
     * @param Context                    $context
     * @param Registry                   $registry
     * @param ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory      $customAttributeFactory
     * @param Data                       $paymentData
     * @param ScopeConfigInterface       $scopeConfig
     * @param Logger                     $logger
     * @param ObjectManagerInterface     $objectManager
     * @param MollieHelper               $mollieHelper
     * @param CheckoutSession            $checkoutSession
     * @param StoreManagerInterface      $storeManager
     * @param Order                      $order
     * @param OrderSender                $orderSender
     * @param InvoiceSender              $invoiceSender
     * @param OrderRepository            $orderRepository
     * @param SearchCriteriaBuilder      $searchCriteriaBuilder
     * @param AbstractResource|null      $resource
     * @param AbstractDb|null            $resourceCollection
     * @param array                      $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        ObjectManagerInterface $objectManager,
        MollieHelper $mollieHelper,
        CheckoutSession $checkoutSession,
        StoreManagerInterface $storeManager,
        Order $order,
        OrderSender $orderSender,
        InvoiceSender $invoiceSender,
        OrderRepository $orderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->objectManager = $objectManager;
        $this->mollieHelper = $mollieHelper;
        $this->checkoutSession = $checkoutSession;
        $this->storeManager = $storeManager;
        $this->scopeConfig = $scopeConfig;
        $this->order = $order;
        $this->orderSender = $orderSender;
        $this->invoiceSender = $invoiceSender;
        $this->orderRepository = $orderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * Extra checks for method availability
     *
     * @param \Magento\Quote\Api\Data\CartInterface|null $quote
     *
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {

        if ($quote == null) {
            $quote = $this->checkoutSession->getQuote();
        }

        if (!$this->mollieHelper->isAvailable($quote->getStoreId())) {
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
     * @param \Magento\Sales\Model\Order $order
     *
     * @return bool
     * @throws \Exception
     * @throws \Mollie\Api\Exceptions\ApiException
     */
    public function startTransaction(Order $order)
    {
        $issuer = null;
        $storeId = $order->getStoreId();
        $orderId = $order->getId();
        $additionalData = $order->getPayment()->getAdditionalInformation();

        if (isset($additionalData['selected_issuer'])) {
            $issuer = $additionalData['selected_issuer'];
        }

        if (!$apiKey = $this->mollieHelper->getApiKey($storeId)) {
            return false;
        }

        $mollieApi = $this->loadMollieApi($apiKey);
        $transactionId = $order->getMollieTransactionId();
        if (!empty($transactionId)) {
            $paymentData = $mollieApi->payments->get($transactionId);
            if (!empty($paymentData->getCheckoutUrl)) {
                return $this->mollieHelper->getRedirectUrl($orderId);
            }

            return $this->mollieHelper->getCheckoutUrl();
        }

        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();
        $method = $this->mollieHelper->getMethodCode($order);

        $paymentData = [
            'amount'      => $this->mollieHelper->getOrderAmountByOrder($order),
            'description' => $order->getIncrementId(),
            'redirectUrl' => $this->mollieHelper->getRedirectUrl($orderId),
            'webhookUrl'  => $this->mollieHelper->getWebhookUrl(),
            'method'      => $method,
            'issuer'      => $issuer,
            'metadata'    => [
                'order_id' => $orderId,
                'store_id' => $order->getStoreId()
            ],
            'locale'      => $this->mollieHelper->getLocaleCode($storeId)
        ];

        if ($method == 'banktransfer') {
            $paymentData['billingEmail'] = $order->getCustomerEmail();
            $paymentData['dueDate'] = $this->mollieHelper->getBanktransferDueDate($storeId);
        }

        if ($billingAddress) {
            $paymentData['billingAddress'] = [
                'streetAndNumber' => $billingAddress->getStreetLine(1),
                'postalCode'      => $billingAddress->getPostcode(),
                'city'            => $billingAddress->getCity(),
                'region'          => $billingAddress->getRegion(),
                'country'         => $billingAddress->getCountryId()
            ];
        }

        if ($shippingAddress) {
            $paymentData['shippingAddress'] = [
                'streetAndNumber' => $shippingAddress->getStreetLine(1),
                'postalCode'      => $shippingAddress->getPostcode(),
                'city'            => $shippingAddress->getCity(),
                'region'          => $shippingAddress->getRegion(),
                'country'         => $shippingAddress->getCountryId()
            ];
        }

        $paymentData = $this->mollieHelper->validatePaymentData($paymentData);
        $this->mollieHelper->addTolog('request', $paymentData);

        $payment = $mollieApi->payments->create($paymentData);
        $paymentUrl = $payment->getCheckoutUrl();
        $transactionId = $payment->id;

        $message = __('Customer redirected to Mollie, url: %1', $paymentUrl);
        $status = $this->mollieHelper->getStatusPending($storeId);
        $order->addStatusToHistory($status, $message, false);
        $order->setMollieTransactionId($transactionId);
        $order->save();

        return $paymentUrl;
    }

    /**
     * @param $apiKey
     *
     * @return \Mollie\Api\MollieApiClient
     * @throws \Mollie\Api\Exceptions\ApiException
     * @throws LocalizedException
     */
    public function loadMollieApi($apiKey)
    {
        if (class_exists('Mollie\Api\MollieApiClient')) {
            /** @var \Mollie\Payment\Model\Api $mollieApi */
            $mollieApi = $this->objectManager->create('Mollie\Payment\Model\Api');
            return $mollieApi->load($apiKey);
        } else {
            throw new LocalizedException(__('Class Mollie\Api\MollieApiClient does not exist'));
        }
    }

    /**
     * Process Transaction (webhook / success)
     *
     * @param        $orderId
     * @param string $type
     *
     * @return array
     * @throws \Exception
     */
    public function processTransaction($orderId, $type = 'webhook')
    {
        $order = $this->order->load($orderId);
        if (empty($order)) {
            $msg = ['error' => true, 'msg' => __('Order not found')];
            $this->mollieHelper->addTolog('error', $msg);

            return $msg;
        }

        $storeId = $order->getStoreId();

        $transactionId = $order->getMollieTransactionId();
        if (empty($transactionId)) {
            $msg = ['error' => true, 'msg' => __('Transaction ID not found')];
            $this->mollieHelper->addTolog('error', $msg);

            return $msg;
        }

        $apiKey = $this->mollieHelper->getApiKey($storeId);
        if (empty($apiKey)) {
            $msg = ['error' => true, 'msg' => __('Api key not found')];
            $this->mollieHelper->addTolog('error', $msg);

            return $msg;
        }

        $mollieApi = $this->loadMollieApi($apiKey);

        $paymentData = $mollieApi->payments->get($transactionId);
        $this->mollieHelper->addTolog($type, $paymentData);

        $status = $paymentData->status;
        $refunded = isset($paymentData->_links->refunds) ? true : false;

        if ($status == 'paid' && !$refunded) {

            $amount = $paymentData->amount->value;
            $currency = $paymentData->amount->currency;
            $orderAmount = $this->mollieHelper->getOrderAmountByOrder($order);

            if ($currency != $orderAmount['currency']) {
                $msg = ['success' => false, 'status' => 'paid', 'order_id' => $orderId, 'type' => $type];
                $this->mollieHelper->addTolog('error', __('Currency does not match.'));
                return $msg;
            }

            $payment = $order->getPayment();
            if (!$payment->getIsTransactionClosed() && $type == 'webhook') {

                if (abs($amount - $orderAmount['value']) < 0.01) {
                    $payment->setTransactionId($transactionId);
                    $payment->setCurrencyCode($order->getBaseCurrencyCode());
                    $payment->setIsTransactionClosed(true);
                    $payment->registerCaptureNotification($order->getBaseGrandTotal(), true);
                    $this->orderRepository->save($order);

                    if ($paymentData->settlementAmount !== null) {
                        if ($paymentData->amount->currency != $paymentData->settlementAmount->currency) {
                            $message = __(
                                'Mollie: Captured %1, Settlement Amount %2',
                                $paymentData->amount->currency . ' ' . $paymentData->amount->value,
                                $paymentData->settlementAmount->currency . ' ' . $paymentData->settlementAmount->value
                            );
                            $order->addStatusHistoryComment($message)->save();
                        }
                    }
                }

                $invoice = $payment->getCreatedInvoice();
                $sendInvoice = $this->mollieHelper->sendInvoice($storeId);

                if (!$order->getEmailSent()) {
                    $this->orderSender->send($order);
                    $message = __('New order email sent');
                    $order->addStatusHistoryComment($message)->setIsCustomerNotified(true)->save();
                }

                if ($invoice && !$invoice->getEmailSent() && $sendInvoice) {
                    $this->invoiceSender->send($invoice);
                    $message = __('Notified customer about invoice #%1', $invoice->getIncrementId());
                    $order->addStatusHistoryComment($message)->setIsCustomerNotified(true)->save();
                }

                if (!$order->getIsVirtual()) {
                    $defaultStatusProcessing = $this->mollieHelper->getStatusProcessing($storeId);
                    if ($defaultStatusProcessing && ($defaultStatusProcessing != $order->getStatus())) {
                        $order->setStatus($defaultStatusProcessing)->save();
                    }
                }
            }

            $msg = ['success' => true, 'status' => 'paid', 'order_id' => $orderId, 'type' => $type];
            $this->mollieHelper->addTolog('success', $msg);
            return $msg;
        }

        if ($refunded) {
            $msg = ['success' => true, 'status' => 'refunded', 'order_id' => $orderId, 'type' => $type];
            $this->mollieHelper->addTolog('success', $msg);
            return $msg;
        }

        if ($status == 'open') {
            if ($paymentData->method == 'banktransfer' && !$order->getEmailSent()) {
                $this->orderSender->send($order);
                $message = __('New order email sent');
                $status = $this->mollieHelper->getStatusPendingBanktransfer($storeId);
                $order->setState(Order::STATE_PENDING_PAYMENT);
                $order->addStatusToHistory($status, $message, true)->save();
            }
            $msg = ['success' => true, 'status' => 'open', 'order_id' => $orderId, 'type' => $type];
            $this->mollieHelper->addTolog('success', $msg);
            return $msg;
        }

        if ($status == 'pending') {
            $msg = ['success' => true, 'status' => 'pending', 'order_id' => $orderId, 'type' => $type];
            $this->mollieHelper->addTolog('success', $msg);
            return $msg;
        }

        if ($status == 'canceled') {
            if ($type == 'webhook') {
                $this->cancelOrder($order, $status);
            }
            $msg = ['success' => false, 'status' => 'cancel', 'order_id' => $orderId, 'type' => $type];
            $this->mollieHelper->addTolog('success', $msg);
            return $msg;
        }

        if ($status == 'failed') {
            if ($type == 'webhook') {
                $this->cancelOrder($order, $status);
            }
            $msg = ['success' => false, 'status' => 'failed', 'order_id' => $orderId, 'type' => $type];
            $this->mollieHelper->addTolog('success', $msg);
            return $msg;
        }

        $msg = ['success' => false, 'status' => $status, 'order_id' => $orderId, 'type' => $type];
        $this->mollieHelper->addTolog('success', $msg);
        return $msg;
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @param                            $status
     *
     * @return bool
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function cancelOrder($order, $status)
    {
        if ($order->getId() && $order->getState() != Order::STATE_CANCELED) {
            $comment = __("The order was %1", $status);
            $this->mollieHelper->addTolog('info', $order->getIncrementId() . ' ' . $comment);
            $order->registerCancellation($comment)->save();

            return true;
        }

        return false;
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

        if (is_array($data)) {
            $this->getInfoInstance()->setAdditionalInformation('selected_issuer', $data['selected_issuer']);
        } elseif ($data instanceof \Magento\Framework\DataObject) {
            $additionalData = $data->getAdditionalData();
            if (isset($additionalData['selected_issuer'])) {
                $issuer = $additionalData['selected_issuer'];
                $this->getInfoInstance()->setAdditionalInformation('selected_issuer', $issuer);
            }
        }
        return $this;
    }

    /**
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float                                $amount
     *
     * @return $this|array
     * @throws LocalizedException
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
        /** @var \Magento\Sales\Model\Order $order */
        $order = $payment->getOrder();
        $storeId = $order->getStoreId();

        $transactionId = $order->getMollieTransactionId();
        if (empty($transactionId)) {
            $msg = ['error' => true, 'msg' => __('Transaction ID not found')];
            $this->mollieHelper->addTolog('error', $msg);

            return $msg;
        }

        $apiKey = $this->mollieHelper->getApiKey($storeId);
        if (empty($apiKey)) {
            $msg = ['error' => true, 'msg' => __('Api key not found')];
            $this->mollieHelper->addTolog('error', $msg);

            return $msg;
        }

        try {
            $mollieApi = $this->loadMollieApi($apiKey);
            $payment = $mollieApi->payments->get($transactionId);
            $payment->refund([
                "amount" => [
                    "currency" => $order->getOrderCurrencyCode(),
                    "value"    => $this->mollieHelper->formatCurrencyValue($amount, $order->getOrderCurrencyCode())
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
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('mollie_transaction_id', $transactionId, 'eq')->create();
        $orderList = $this->orderRepository->getList($searchCriteria);
        $orderId = $orderList->getFirstItem()->getId();

        if ($orderId) {
            return $orderId;
        } else {
            $this->mollieHelper->addTolog('error', __('No order found for transaction id %1', $transactionId));

            return false;
        }
    }

    /**
     * Get list of Issuers from API
     *
     * @param $mollieApi
     * @param $method
     *
     * @return array|bool
     */
    public function getIssuers($mollieApi, $method)
    {
        $issuers = [];

        if (empty($mollieApi)) {
            return false;
        }

        $methodCode = str_replace('mollie_methods_', '', $method);

        try {
            $issuersList = $mollieApi->methods->get($methodCode, ["include" => "issuers"])->issuers;
            foreach ($issuersList as $issuer) {
                $issuers[] = $issuer;
            }
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
        }

        return $issuers;
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

        return $mollieApi->methods->all();
    }

}
