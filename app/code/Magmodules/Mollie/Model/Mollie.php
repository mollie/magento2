<?php
/**
 * Copyright © 2016 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Mollie\Model;

use Mollie_API_Client as MollieApi;

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
use Magmodules\Mollie\Helper\General as MollieHelper;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\OrderRepository;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Checkout\Model\Session as CheckoutSession;

class Mollie extends AbstractMethod
{

    protected $_isGateway = true;
    protected $_isOffline = false;
    protected $_canRefund = true;
    protected $issuers = [];

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
     * @param MollieApi                  $mollieApi
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
        MollieApi $mollieApi,
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

        $this->mollieApi = $mollieApi;
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

        if (!$this->mollieHelper->isCurrencyAllowed($quote->getBaseCurrencyCode())) {
            return false;
        }

        return parent::isAvailable($quote);
    }

    /**
     * Start transaction
     *
     * @param Order $order
     *
     * @return bool
     */
    public function startTransaction(Order $order, $issuer = '')
    {
        $storeId = $order->getStoreId();
        $orderId = $order->getId();

        if (!$apiKey = $this->mollieHelper->getApiKey($storeId)) {
            return false;
        }

        $this->mollieApi->setApiKey($apiKey);
        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress();

        $paymentData = [
            'amount'      => $order->getBaseGrandTotal(),
            'description' => $order->getIncrementId(),
            'redirectUrl' => $this->mollieHelper->getRedirectUrl($orderId),
            'webhookUrl'  => $this->mollieHelper->getWebhookUrl(),
            'method'      => $this->mollieHelper->getMethodCode($order),
            'issuer'      => $issuer,
            'metadata'    => [
                'order_id' => $orderId,
                'store_id' => $order->getStoreId()
            ]
        ];

        if ($billingAddress) {
            $billingData = [
                'billingAddress' => $billingAddress->getStreetLine(1),
                'billingCity'    => $billingAddress->getCity(),
                'billingRegion'  => $billingAddress->getRegion(),
                'billingPostal'  => $billingAddress->getPostcode(),
                'billingCountry' => $billingAddress->getCountryId()
            ];
            $paymentData = array_merge($paymentData, $billingData);
        }

        if ($shippingAddress) {
            $shippingData = [
                'shippingAddress' => $shippingAddress->getStreetLine(1),
                'shippingCity'    => $shippingAddress->getCity(),
                'shippingRegion'  => $shippingAddress->getRegion(),
                'shippingPostal'  => $shippingAddress->getPostcode(),
                'shippingCountry' => $shippingAddress->getCountryId()
            ];
            $paymentData = array_merge($paymentData, $shippingData);
        }

        $this->mollieHelper->addTolog('request', $paymentData);

        $payment = $this->mollieApi->payments->create($paymentData);
        $paymentUrl = $payment->getPaymentUrl();
        $transactionId = $payment->id;

        $message = __('Customer redirected to Mollie, url: %1', $paymentUrl);
        $status = $this->mollieHelper->getStatusPending($storeId);
        $order->addStatusToHistory($status, $message, false);
        $order->setMollieTransactionId($transactionId);
        $order->save();

        return $paymentUrl;
    }

    /**
     * Process Transaction (webhook / success)
     *
     * @param        $orderId
     * @param string $type
     *
     * @return array
     */
    public function processTransaction($orderId, $type = 'webhook')
    {
        $msg = '';

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

        $this->mollieApi->setApiKey($apiKey);
        $paymentData = $this->mollieApi->payments->get($transactionId);
        $this->mollieHelper->addTolog($type, $paymentData);

        if (($paymentData->isPaid() == true) && ($paymentData->isRefunded() == false)) {
            $amount = $paymentData->amount;
            $payment = $order->getPayment();

            if (!$payment->getIsTransactionClosed()) {
                $payment->setTransactionId($transactionId);
                $payment->setCurrencyCode('EUR');
                $payment->setIsTransactionClosed(true);
                $payment->registerCaptureNotification($amount, true);
                $order->save();

                $invoice = $payment->getCreatedInvoice();
                $status = $this->mollieHelper->getStatusProcessing($storeId);
                $sendInvoice = $this->mollieHelper->sendInvoice($storeId);

                if ($invoice && !$order->getEmailSent()) {
                    $this->orderSender->send($order);
                    $message = __('New order email sent', $paymentUrl);
                    $order->addStatusToHistory($status, $message, true)->save();
                }
                if ($invoice && !$invoice->getEmailSent() && $sendInvoice) {
                    $this->invoiceSender->send($invoice);
                    $message = __('Notified customer about invoice #%1', $invoice->getIncrementId());
                    $order->addStatusToHistory($status, $message, true)->save();
                }

                $msg = ['success' => true, 'status' => 'paid', 'order_id' => $orderId, 'type' => $type];
                $this->mollieHelper->addTolog('sucess', $msg);
            }
        } elseif ($paymentData->isRefunded() == true) {
            $msg = ['success' => true, 'status' => 'refunded', 'order_id' => $orderId, 'type' => $type];
            $this->mollieHelper->addTolog('sucess', $msg);
        } elseif ($paymentData->isOpen() == true) {
            $msg = ['success' => true, 'status' => 'open', 'order_id' => $orderId, 'type' => $type];
            $this->mollieHelper->addTolog('sucess', $msg);
        } elseif ($paymentData->isPending() == true) {
            $msg = ['success' => true, 'status' => 'pending', 'order_id' => $orderId, 'type' => $type];
            $this->mollieHelper->addTolog('sucess', $msg);
        } elseif (!$paymentData->isOpen()) {
            $this->cancelOrder($order);
            $msg = ['success' => false, 'status' => 'cancel', 'order_id' => $orderId, 'type' => $type];
            $this->mollieHelper->addTolog('sucess', $msg);
        }

        return $msg;
    }

    /**
     * Cancel order
     *
     * @param $order
     *
     * @return bool
     */
    protected function cancelOrder($order)
    {
        if ($order->getId() && $order->getState() != Order::STATE_CANCELED) {
            $comment = __("The order was canceled");
            $this->mollieHelper->addTolog('info', $order->getIncrementId() . ' ' . $comment);
            $order->registerCancellation($comment)->save();

            return true;
        }

        return false;
    }

    /**
     * Refund Transaction
     *
     * @param \Magento\Payment\Model\InfoInterface $payment
     * @param float                                $amount
     *
     * @return $this|array
     */
    public function refund(\Magento\Payment\Model\InfoInterface $payment, $amount)
    {
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

        $this->mollieApi->setApiKey($apiKey);

        try {
            $payment = $this->mollieApi->payments->get($transactionId);
            $this->mollieApi->payments->refund($payment, $amount);
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
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
     * Get list of iDeal Issuers from API
     *
     * @return array
     */
    public function getIssuers()
    {
        $issuers = [];
        $storeId = $this->storeManager->getStore()->getId();
        $apiKey = $this->mollieHelper->getApiKey($storeId);

        if (empty($apiKey)) {
            return false;
        }

        $this->mollieApi->setApiKey($apiKey);

        try {
            foreach ($this->mollieApi->issuers->all() as $issuer) {
                $issuers[] = $issuer;
            }
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
        }

        return $issuers;
    }

    /**
     * @param $storeId
     *
     * @return bool
     */
    public function getPaymentMethods($storeId)
    {
        $apiKey = $this->mollieHelper->getApiKey($storeId);

        if (empty($apiKey)) {
            return false;
        }

        $this->mollieApi->setApiKey($apiKey);
        return $this->mollieApi->methods->all();
    }
}
