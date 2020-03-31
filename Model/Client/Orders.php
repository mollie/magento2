<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Client;

use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\InvoiceRepository;
use Magento\Sales\Model\ResourceModel\Order\Handler\State;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Checkout\Model\Session as CheckoutSession;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Types\OrderStatus;
use Mollie\Payment\Config;
use Mollie\Payment\Helper\General as MollieHelper;
use Mollie\Payment\Model\Adminhtml\Source\InvoiceMoment;
use Mollie\Payment\Model\OrderLines;
use Mollie\Payment\Service\Mollie\Order\RefundUsingPayment;
use Mollie\Payment\Service\Mollie\Order\Transaction\Expires;
use Mollie\Payment\Service\Order\BuildTransaction;
use Mollie\Payment\Service\Order\Lines\StoreCredit;
use Mollie\Payment\Service\Order\OrderCommentHistory;
use Mollie\Payment\Service\Order\PartialInvoice;
use Mollie\Payment\Service\Order\ProcessAdjustmentFee;
use Mollie\Payment\Service\Order\Transaction;

/**
 * Class Orders
 *
 * @package Mollie\Payment\Model\Client
 */
class Orders extends AbstractModel
{

    const CHECKOUT_TYPE = 'order';

    /**
     * @var MollieHelper
     */
    private $mollieHelper;
    /**
     * @var OrderLines
     */
    private $orderLines;
    /**
     * @var OrderRepository
     */
    private $orderRepository;
    /**
     * @var InvoiceRepository
     */
    private $invoiceRepository;
    /**
     * @var OrderSender
     */
    private $orderSender;
    /**
     * @var InvoiceSender
     */
    private $invoiceSender;
    /**
     * @var InvoiceService
     */
    private $invoiceService;
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    /**
     * @var ManagerInterface
     */
    private $messageManager;
    /**
     * @var Registry
     */
    private $registry;
    /**
     * @var ProcessAdjustmentFee
     */
    private $adjustmentFee;
    /**
     * @var StoreCredit
     */
    private $storeCredit;
    /**
     * @var RefundUsingPayment
     */
    private $refundUsingPayment;
    /**
     * @var OrderCommentHistory
     */
    private $orderCommentHistory;
    /**
     * @var PartialInvoice
     */
    private $partialInvoice;
    /**
     * @var Expires
     */
    private $expires;
    /**
     * @var State
     */
    private $orderState;
    /**
     * @var Transaction
     */
    private $transaction;
    /**
     * @var BuildTransaction
     */
    private $buildTransaction;
    /**
     * @var Config
     */
    private $config;

    /**
     * Orders constructor.
     *
     * @param OrderLines            $orderLines
     * @param OrderSender           $orderSender
     * @param InvoiceSender         $invoiceSender
     * @param InvoiceService        $invoiceService
     * @param OrderRepository       $orderRepository
     * @param InvoiceRepository     $invoiceRepository
     * @param CheckoutSession       $checkoutSession
     * @param ManagerInterface      $messageManager
     * @param Registry              $registry
     * @param MollieHelper          $mollieHelper
     * @param ProcessAdjustmentFee  $adjustmentFee
     * @param OrderCommentHistory   $orderCommentHistory
     * @param PartialInvoice        $partialInvoice
     * @param StoreCredit           $storeCredit
     * @param RefundUsingPayment    $refundUsingPayment
     * @param Expires               $expires
     * @param State                 $orderState
     * @param Transaction           $transaction
     * @param BuildTransaction      $buildTransaction
     * @param Config                $config
     */
    public function __construct(
        OrderLines $orderLines,
        OrderSender $orderSender,
        InvoiceSender $invoiceSender,
        InvoiceService $invoiceService,
        OrderRepository $orderRepository,
        InvoiceRepository $invoiceRepository,
        CheckoutSession $checkoutSession,
        ManagerInterface $messageManager,
        Registry $registry,
        MollieHelper $mollieHelper,
        ProcessAdjustmentFee $adjustmentFee,
        OrderCommentHistory $orderCommentHistory,
        PartialInvoice $partialInvoice,
        StoreCredit $storeCredit,
        RefundUsingPayment $refundUsingPayment,
        Expires $expires,
        State $orderState,
        Transaction $transaction,
        BuildTransaction $buildTransaction,
        Config $config
    ) {
        $this->orderLines = $orderLines;
        $this->orderSender = $orderSender;
        $this->invoiceSender = $invoiceSender;
        $this->invoiceService = $invoiceService;
        $this->orderRepository = $orderRepository;
        $this->invoiceRepository = $invoiceRepository;
        $this->checkoutSession = $checkoutSession;
        $this->messageManager = $messageManager;
        $this->registry = $registry;
        $this->mollieHelper = $mollieHelper;
        $this->adjustmentFee = $adjustmentFee;
        $this->storeCredit = $storeCredit;
        $this->refundUsingPayment = $refundUsingPayment;
        $this->orderCommentHistory = $orderCommentHistory;
        $this->partialInvoice = $partialInvoice;
        $this->expires = $expires;
        $this->orderState = $orderState;
        $this->transaction = $transaction;
        $this->buildTransaction = $buildTransaction;
        $this->config = $config;
    }

    /**
     * @param Order $order
     * @param MollieApiClient $mollieApi
     *
     * @return string
     * @throws LocalizedException
     * @throws ApiException
     */
    public function startTransaction(Order $order, $mollieApi)
    {
        $storeId = $order->getStoreId();
        $orderId = $order->getEntityId();
        $additionalData = $order->getPayment()->getAdditionalInformation();

        $transactionId = $order->getMollieTransactionId();
        if (!empty($transactionId)) {
            $mollieOrder = $mollieApi->orders->get($transactionId);
            return $mollieOrder->getCheckoutUrl();
        }

        $paymentToken = $this->mollieHelper->getPaymentToken();
        $method = $this->mollieHelper->getMethodCode($order);
        $orderData = [
            'amount'              => $this->mollieHelper->getOrderAmountByOrder($order),
            'orderNumber'         => $order->getIncrementId(),
            'billingAddress'      => $this->getAddressLine($order->getBillingAddress()),
            'consumerDateOfBirth' => null,
            'lines'               => $this->orderLines->getOrderLines($order),
            'redirectUrl'         => $this->transaction->getRedirectUrl($orderId, $paymentToken),
            'webhookUrl'          => $this->transaction->getWebhookUrl(),
            'locale'              => $this->mollieHelper->getLocaleCode($storeId, self::CHECKOUT_TYPE),
            'method'              => $method,
            'metadata'            => [
                'order_id'      => $orderId,
                'store_id'      => $order->getStoreId(),
                'payment_token' => $paymentToken
            ],
        ];

        if (!$order->getIsVirtual() && $order->hasData('shipping_address_id')) {
            $orderData['shippingAddress'] = $this->getAddressLine($order->getShippingAddress());
        }

        if (isset($additionalData['selected_issuer'])) {
            $orderData['payment']['issuer'] = $additionalData['selected_issuer'];
        }

        if ($method == 'banktransfer') {
            $orderData['payment']['dueDate'] = $this->mollieHelper->getBanktransferDueDate($storeId);
        }

        if (isset($additionalData['limited_methods'])) {
            $orderData['method'] = $additionalData['limited_methods'];
        }

        if ($this->expires->availableForMethod($method, $storeId)) {
            $orderData['expiresAt'] = $this->expires->atDateForMethod($method, $storeId);
        }

        $orderData = $this->buildTransaction->execute($order, static::CHECKOUT_TYPE, $orderData);

        $this->mollieHelper->addTolog('request', $orderData);
        $mollieOrder = $mollieApi->orders->create($orderData);
        $this->processResponse($order, $mollieOrder);

        return $mollieOrder->getCheckoutUrl();
    }

    /**
     * @param \Magento\Sales\Api\Data\OrderAddressInterface $address
     *
     * @return array
     */
    public function getAddressLine($address)
    {
        return [
            'organizationName' => $address->getCompany(),
            'title'            => trim($address->getPrefix()),
            'givenName'        => $address->getFirstname(),
            'familyName'       => $address->getLastname(),
            'email'            => $address->getEmail(),
            'streetAndNumber'  => rtrim(implode(' ', $address->getStreet()), ' '),
            'postalCode'       => $address->getPostcode(),
            'city'             => $address->getCity(),
            'region'           => $address->getRegion(),
            'country'          => $address->getCountryId(),
        ];
    }

    /**
     * @param Order $order
     * @param       $mollieOrder
     *
     * @throws LocalizedException
     */
    public function processResponse(Order $order, $mollieOrder)
    {
        $this->mollieHelper->addTolog('response', $mollieOrder);
        $order->getPayment()->setAdditionalInformation('checkout_url', $mollieOrder->getCheckoutUrl());
        $order->getPayment()->setAdditionalInformation('checkout_type', self::CHECKOUT_TYPE);
        $order->getPayment()->setAdditionalInformation('payment_status', $mollieOrder->status);
        if (isset($mollieOrder->expiresAt)) {
            $order->getPayment()->setAdditionalInformation('expires_at', $mollieOrder->expiresAt);
        }

        $this->orderLines->linkOrderLines($mollieOrder->lines, $order);

        $status = $this->mollieHelper->getPendingPaymentStatus($order);

        $msg = __('Customer redirected to Mollie');
        if ($order->getPayment()->getMethodInstance()->getCode() == 'mollie_methods_paymentlink') {
            $msg = __('Created Mollie Checkout Url');
        }

        $order->addStatusToHistory($status, $msg, false);
        $order->setMollieTransactionId($mollieOrder->id);
        $this->orderRepository->save($order);
    }

    /**
     * @param Order                       $order
     * @param MollieApiClient $mollieApi
     * @param string                      $type
     * @param null                        $paymentToken
     *
     * @return array
     * @throws ApiException
     * @throws LocalizedException
     */
    public function processTransaction(Order $order, $mollieApi, $type = 'webhook', $paymentToken = null)
    {
        $orderId = $order->getId();
        $storeId = $order->getStoreId();
        $transactionId = $order->getMollieTransactionId();
        $mollieOrder = $mollieApi->orders->get($transactionId, ["embed" => "payments"]);
        $this->mollieHelper->addTolog($type, $mollieOrder);
        $status = $mollieOrder->status;

        $this->orderLines->updateOrderLinesByWebhook($mollieOrder->lines, $mollieOrder->isPaid());

        /**
         * Check if last payment was canceled, failed or expired and redirect customer to cart for retry.
         */
        $lastPaymentStatus = $this->mollieHelper->getLastRelevantStatus($mollieOrder);
        if ($lastPaymentStatus == 'canceled' || $lastPaymentStatus == 'failed' || $lastPaymentStatus == 'expired') {
            $method = $order->getPayment()->getMethodInstance()->getTitle();
            $order->getPayment()->setAdditionalInformation('payment_status', $lastPaymentStatus);
            $this->orderRepository->save($order);
            $this->mollieHelper->registerCancellation($order, $lastPaymentStatus);
            $msg = ['success' => false, 'status' => $lastPaymentStatus, 'order_id' => $orderId, 'type' => $type, 'method' => $method];
            $this->mollieHelper->addTolog('success', $msg);
            return $msg;
        }

        $refunded = $mollieOrder->amountRefunded !== null ? true : false;
        $payment = $order->getPayment();
        if ($type == 'webhook' && $payment->getAdditionalInformation('payment_status') != $status) {
            $payment->setAdditionalInformation('payment_status', $status);
            $this->orderRepository->save($order);
        }

        if (($mollieOrder->isPaid() || $mollieOrder->isAuthorized()) && !$refunded) {
            $amount = $mollieOrder->amount->value;
            $currency = $mollieOrder->amount->currency;
            $orderAmount = $this->mollieHelper->getOrderAmountByOrder($order);

            if ($currency != $orderAmount['currency']) {
                $msg = ['success' => false, 'status' => 'paid', 'order_id' => $orderId, 'type' => $type];
                $this->mollieHelper->addTolog('error', __('Currency does not match.'));
                return $msg;
            }

            if (!$payment->getIsTransactionClosed() && $type == 'webhook') {
                if ($order->isCanceled()) {
                    $order = $this->mollieHelper->uncancelOrder($order);
                }

                if (abs($amount - $orderAmount['value']) < 0.01) {
                    $payment->setTransactionId($transactionId);
                    $payment->setCurrencyCode($order->getBaseCurrencyCode());

                    if ($mollieOrder->isPaid()) {
                        $payment->setIsTransactionClosed(true);
                        $payment->registerCaptureNotification($order->getBaseGrandTotal(), true);
                    }

                    if ($mollieOrder->isAuthorized() &&
                        $this->mollieHelper->getInvoiceMoment($storeId) == InvoiceMoment::ON_AUTHORIZE
                    ) {
                        $payment->setIsTransactionClosed(false);
                        $payment->registerAuthorizationNotification($order->getBaseGrandTotal(), true);

                        /**
                         * Create pending invoice, as order has not been paid.
                         */
                        $invoice = $this->invoiceService->prepareInvoice($order);
                        $invoice->setRequestedCaptureCase(Invoice::NOT_CAPTURE);
                        $invoice->setTransactionId($transactionId);
                        $invoice->register();

                        $this->invoiceRepository->save($invoice);
                    }

                    $order->setState(Order::STATE_PROCESSING);

                    if ($mollieOrder->amountCaptured !== null) {
                        if ($mollieOrder->amount->currency != $mollieOrder->amountCaptured->currency) {
                            $message = __(
                                'Mollie: Order Amount %1, Captures Amount %2',
                                $mollieOrder->amount->currency . ' ' . $mollieOrder->amount->value,
                                $mollieOrder->amountCaptured->currency . ' ' . $mollieOrder->amountCaptured->value
                            );
                            $this->orderCommentHistory->add($order, $message);
                        }
                    }

                    if (!$order->getIsVirtual()) {
                        $defaultStatusProcessing = $this->mollieHelper->getStatusProcessing($storeId);
                        if ($defaultStatusProcessing && ($defaultStatusProcessing != $order->getStatus())) {
                            $order->setStatus($defaultStatusProcessing);
                        }
                    }

                    $this->orderRepository->save($order);
                }

                /** @var Order\Invoice $invoice */
                $invoice = $payment->getCreatedInvoice();
                $sendInvoice = $this->mollieHelper->sendInvoice($storeId);

                if (!$order->getEmailSent()) {
                    try {
                        $this->orderSender->send($order, true);
                        $message = __('New order email sent');
                        $this->orderCommentHistory->add($order, $message, true);
                    } catch (\Throwable $exception) {
                        $message = __('Unable to send the new order email: %1', $exception->getMessage());
                        $this->orderCommentHistory->add($order, $message, false);
                    }
                }

                if ($invoice && !$invoice->getEmailSent() && $sendInvoice) {
                    try {
                        $this->invoiceSender->send($invoice);
                        $message = __('Notified customer about invoice #%1', $invoice->getIncrementId());
                        $this->orderCommentHistory->add($order, $message, true);
                    } catch (\Throwable $exception) {
                        $message = __('Unable to send the invoice: %1', $exception->getMessage());
                        $this->orderCommentHistory->add($order, $message, true);
                    }
                }

            }

            $msg = ['success' => true, 'status' => $status, 'order_id' => $orderId, 'type' => $type];
            $this->mollieHelper->addTolog('success', $msg);
            $this->checkCheckoutSession($order, $paymentToken, $mollieOrder, $type);
            return $msg;
        }

        if ($refunded) {
            $msg = ['success' => true, 'status' => 'refunded', 'order_id' => $orderId, 'type' => $type];
            $this->mollieHelper->addTolog('success', $msg);
            return $msg;
        }

        if ($mollieOrder->isCreated()) {
            if ($mollieOrder->method == 'banktransfer' && !$order->getEmailSent()) {
                try {
                    $this->orderSender->send($order);
                    $message = __('New order email sent');
                } catch (\Throwable $exception) {
                    $message = __('Unable to send the new order email: %1', $exception->getMessage());
                }

                if (!$statusPending = $this->mollieHelper->getStatusPendingBanktransfer($storeId)) {
                    $statusPending = $order->getStatus();
                }

                $order->setState(Order::STATE_PENDING_PAYMENT);
                $order->addStatusToHistory($statusPending, $message, true);
                $this->orderRepository->save($order);
            }
            $msg = ['success' => true, 'status' => $status, 'order_id' => $orderId, 'type' => $type];
            $this->mollieHelper->addTolog('success', $msg);
            $this->checkCheckoutSession($order, $paymentToken, $mollieOrder, $type);
            return $msg;
        }

        if ($mollieOrder->isCanceled() || $mollieOrder->isExpired()) {
            if ($type == 'webhook') {
                $this->mollieHelper->registerCancellation($order, $status);
            }
            $msg = ['success' => false, 'status' => $status, 'order_id' => $orderId, 'type' => $type];
            $this->mollieHelper->addTolog('success', $msg);
            return $msg;
        }

        if ($mollieOrder->isCompleted()) {
            $msg = ['success' => true, 'status' => $status, 'order_id' => $orderId, 'type' => $type];
            $this->mollieHelper->addTolog('success', $msg);
            return $msg;
        }

        $msg = ['success' => false, 'status' => $status, 'order_id' => $orderId, 'type' => $type];
        $this->mollieHelper->addTolog('success', $msg);
        return $msg;
    }

    public function orderHasUpdate(OrderInterface $order, MollieApiClient $mollieApi)
    {
        $transactionId = $order->getMollieTransactionId();
        $mollieOrder = $mollieApi->orders->get($transactionId);

        $mapping = [
            OrderStatus::STATUS_CREATED => Order::STATE_NEW,
            OrderStatus::STATUS_PAID => Order::STATE_PROCESSING,
            OrderStatus::STATUS_AUTHORIZED => Order::STATE_PROCESSING,
            OrderStatus::STATUS_CANCELED => Order::STATE_CANCELED,
            OrderStatus::STATUS_SHIPPING => Order::STATE_PROCESSING,
            OrderStatus::STATUS_COMPLETED => Order::STATE_COMPLETE,
            OrderStatus::STATUS_EXPIRED => Order::STATE_CANCELED,
            OrderStatus::STATUS_PENDING => Order::STATE_PENDING_PAYMENT,
            OrderStatus::STATUS_REFUNDED => Order::STATE_CLOSED,
        ];

        $expectedStatus = $mapping[$mollieOrder->status];

        return $expectedStatus != $order->getState();
    }

    /**
     * @param Order $order
     * @param       $paymentToken
     * @param       $paymentData
     * @param       $type
     */
    public function checkCheckoutSession(Order $order, $paymentToken, $paymentData, $type)
    {
        if ($type == 'webhook') {
            return;
        }
        if ($this->checkoutSession->getLastOrderId() != $order->getId()) {
            if ($paymentToken && isset($paymentData->metadata->payment_token)) {
                if ($paymentToken == $paymentData->metadata->payment_token) {
                    $this->checkoutSession->setLastQuoteId($order->getQuoteId())
                        ->setLastSuccessQuoteId($order->getQuoteId())
                        ->setLastOrderId($order->getId())
                        ->setLastRealOrderId($order->getIncrementId());
                }
            }
        }
    }

    /**
     * @param OrderInterface $order
     *
     * @return $this
     * @throws LocalizedException
     */
    public function cancelOrder(OrderInterface $order)
    {
        $transactionId = $order->getMollieTransactionId();
        if (empty($transactionId)) {
            $msg = ['error' => true, 'msg' => __('Transaction ID not found')];
            $this->mollieHelper->addTolog('error', $msg);
            return $this;
        }

        $apiKey = $this->mollieHelper->getApiKey($order->getStoreId());
        if (empty($apiKey)) {
            $msg = ['error' => true, 'msg' => __('Api key not found')];
            $this->mollieHelper->addTolog('error', $msg);
            return $this;
        }

        try {
            $mollieApi = $this->loadMollieApi($apiKey);

            $mollieOrder = $mollieApi->orders->get($transactionId);
            if ($mollieOrder->status != 'expired') {
                $mollieApi->orders->cancel($transactionId);
            }
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
            throw new LocalizedException(
                __('Mollie (Order ID: %2): %1', $e->getMessage(), $order->getEntityId())
            );
        }

        return $this;
    }

    /**
     * @param $apiKey
     *
     * @return MollieApiClient
     * @throws ApiException
     * @throws LocalizedException
     */
    public function loadMollieApi($apiKey)
    {
        if (class_exists('Mollie\Api\MollieApiClient')) {
            $mollieApiClient = new MollieApiClient();
            $mollieApiClient->setApiKey($apiKey);
            $mollieApiClient->addVersionString('Magento/' . $this->mollieHelper->getMagentoVersion());
            $mollieApiClient->addVersionString('MollieMagento2/' . $this->mollieHelper->getExtensionVersion());
            return $mollieApiClient;
        } else {
            throw new LocalizedException(__('Class Mollie\Api\MollieApiClient does not exist'));
        }
    }

    /**
     * @param Order\Shipment $shipment
     * @param Order          $order
     *
     * @return $this
     * @throws LocalizedException
     */
    public function createShipment(Order\Shipment $shipment, Order $order)
    {
        $shipAll = false;
        $transactionId = $order->getMollieTransactionId();
        if (empty($transactionId)) {
            $msg = ['error' => true, 'msg' => __('Transaction ID not found')];
            $this->mollieHelper->addTolog('error', $msg);
            return $this;
        }

        $mollieShipmentId = $shipment->getMollieShipmentId();
        if ($mollieShipmentId !== null) {
            $msg = ['error' => true, 'msg' => __('Shipment already pushed to Mollie')];
            $this->mollieHelper->addTolog('error', $msg);
            return $this;
        }

        $apiKey = $this->mollieHelper->getApiKey($order->getStoreId());
        if (empty($apiKey)) {
            $msg = ['error' => true, 'msg' => __('Api key not found')];
            $this->mollieHelper->addTolog('error', $msg);
            return $this;
        }

        /**
         * If products ordered qty equals shipping qty,
         * complete order can be shipped incl. shipping & discount itemLines.
         */
        if ($this->isShippingAllItems($order, $shipment)) {
            $shipAll = true;
        }

        try {
            $mollieApi = $this->loadMollieApi($apiKey);
            $mollieOrder = $mollieApi->orders->get($transactionId);

            if ($mollieOrder->status == 'completed') {
                $this->messageManager->addWarningMessage(
                    __('All items in this order where already marked as shipped in the Mollie dashboard.')
                );
                return $this;
            }

            if ($shipAll) {
                $mollieShipment = $mollieOrder->shipAll();
            } else {
                $orderLines = $this->orderLines->getShipmentOrderLines($shipment);

                if ($mollieOrder->status == 'shipping' && !$this->itemsAreShippable($mollieOrder, $orderLines)) {
                    $this->messageManager->addWarningMessage(
                        __('All items in this order where already marked as shipped in the Mollie dashboard.')
                    );
                    return $this;
                }

                $mollieShipment = $mollieOrder->createShipment($orderLines);
            }

            $mollieShipmentId = isset($mollieShipment) ? $mollieShipment->id : 0;
            $shipment->setMollieShipmentId($mollieShipmentId);

            /**
             * Check if Transactions needs to be captured (eg. Klarna methods)
             */
            $payment = $order->getPayment();
            if (!$payment->getIsTransactionClosed()) {
                $invoice = $order->getInvoiceCollection()->getLastItem();
                if (!$shipAll) {
                    $invoice = $this->partialInvoice->createFromShipment($shipment);
                }

                $captureAmount = $this->getCaptureAmount($order, $invoice && $invoice->getId() ? $invoice : null);

                $payment->setTransactionId($transactionId);
                $payment->registerCaptureNotification($captureAmount, true);
                $this->orderRepository->save($order);

                $sendInvoice = $this->mollieHelper->sendInvoice($order->getStoreId());
                if ($invoice && $invoice->getId() && !$invoice->getEmailSent() && $sendInvoice) {
                    $this->invoiceSender->send($invoice);
                    $message = __('Notified customer about invoice #%1', $invoice->getIncrementId());
                    $this->orderCommentHistory->add($order, $message, true);
                }
            }
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
            throw new LocalizedException(
                __('Mollie API: %1', $e->getMessage())
            );
        }

        return $this;
    }

    /**
     * @param Order\Shipment       $shipment
     * @param Order\Shipment\Track $track
     * @param Order                $order
     *
     * @return Orders
     * @throws LocalizedException
     */
    public function updateShipmentTrack($shipment, $track, $order)
    {
        $transactionId = $order->getMollieTransactionId();
        if (empty($transactionId)) {
            $msg = ['error' => true, 'msg' => __('Transaction ID not found')];
            $this->mollieHelper->addTolog('error', $msg);
            return $this;
        }

        $shipmentId = $shipment->getMollieShipmentId();
        if (empty($shipmentId)) {
            $msg = ['error' => true, 'msg' => __('Shipment ID not found')];
            $this->mollieHelper->addTolog('error', $msg);
            return $this;
        }

        $apiKey = $this->mollieHelper->getApiKey($order->getStoreId());
        if (empty($apiKey)) {
            $msg = ['error' => true, 'msg' => __('Api key not found')];
            $this->mollieHelper->addTolog('error', $msg);
            return $this;
        }

        try {
            $mollieApi = $this->loadMollieApi($apiKey);
            $mollieOrder = $mollieApi->orders->get($transactionId);
            if ($mollieShipment = $mollieOrder->getShipment($shipmentId)) {
                $this->mollieHelper->addTolog(
                    'tracking',
                    sprintf('Added %s shipping for %s', $track->getTitle(), $transactionId)
                );
                $mollieShipment->tracking = [
                    'carrier' => $track->getTitle(),
                    'code'    => $track->getTrackNumber()
                ];
                $mollieShipment->update();
            }
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
        }

        return $this;
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
        $storeId = $order->getStoreId();
        $orderId = $order->getId();

        /**
         * Skip the creation of an online refund if an offline refund is used + add notice msg.
         * Registry set at the Mollie\Payment\Model\Mollie::refund and is set once an online refund is used.
         */
        if (!$this->registry->registry('online_refund')) {
            $this->messageManager->addNoticeMessage(
                __(
                    'An offline refund has been created, please make sure to also create this
                    refund on mollie.com/dashboard or use the online refund option.'
                )
            );
            return $this;
        }

        $methodCode = $this->mollieHelper->getMethodCode($order);
        if (!$order->hasShipments() && ($methodCode == 'klarnapaylater' || $methodCode == 'klarnasliceit')) {
            $msg = __('Order can only be refunded after Klara has been captured (after shipment)');
            throw new LocalizedException($msg);
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
            $mollieApi = $this->loadMollieApi($apiKey);
        } catch (\Exception $exception) {
            $this->mollieHelper->addTolog('error', $exception->getMessage());
            throw new LocalizedException(
                __('Mollie API: %1', $exception->getMessage())
            );
        }

        if ($this->storeCredit->creditmemoHasStoreCredit($creditmemo)) {
            $this->refundUsingPayment->execute(
                $mollieApi,
                $transactionId,
                $creditmemo->getOrderCurrencyCode(),
                $creditmemo->getBaseGrandTotal()
            );

            return $this;
        }

        /**
         * Check for creditmemo adjustment fee's, positive and negative.
         */
        $this->adjustmentFee->handle($mollieApi, $order, $creditmemo);

        /**
         * Check if Shipping Fee needs to be refunded.
         * Throws exception if Shipping Amount of credit does not match Shipping Fee of paid orderLine.
         */
        $addShippingToRefund = null;
        $shippingCostsLine = $this->orderLines->getShippingFeeItemLineOrder($orderId);
        if ($shippingCostsLine->getId() && $shippingCostsLine->getQtyRefunded() == 0) {
            if ($creditmemo->getShippingAmount() > 0) {
                $addShippingToRefund = true;
                if (abs($creditmemo->getShippingInclTax() - $shippingCostsLine->getTotalAmount()) > 0.01) {
                    $msg = __('Can not create online refund, as shipping costs do not match');
                    $this->mollieHelper->addTolog('error', $msg);
                    throw new LocalizedException($msg);
                }
            }
        }

        if (!$creditmemo->getAllItems() || $this->adjustmentFee->doNotRefundInMollie()) {
            return $this;
        }

        try {
            /**
             * Sometimes we don't get the correct state when working with bundles, so manually check it.
             */
            $this->orderState->check($order);
            $mollieOrder = $mollieApi->orders->get($transactionId);
            if ($order->getState() == Order::STATE_CLOSED) {
                $mollieOrder->refundAll();
            } else {
                $orderLines = $this->orderLines->getCreditmemoOrderLines($creditmemo, $addShippingToRefund);
                $mollieOrder->refund($orderLines);
            }
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
            throw new LocalizedException(
                __('Mollie API: %1', $e->getMessage())
            );
        }

        return $this;
    }

    /**
     * When an order line is already marked as shipped in the Mollie dashboard, and we try this action again we get
     * an exception and the user is unable to create an order. This code checks if the selected lines are already
     * marked as shipped. If that's the case a warning will be shown, but the order is still created.
     *
     * @param \Mollie\Api\Resources\Order $mollieOrder
     * @param $orderLines
     * @return bool
     */
    private function itemsAreShippable(\Mollie\Api\Resources\Order $mollieOrder, $orderLines)
    {
        $lines = [];
        foreach ($orderLines['lines'] as $line) {
            $id = $line['id'];
            $lines[$id] = $line['quantity'];
        }

        foreach ($mollieOrder->lines as $line) {
            if (!isset($lines[$line->id])) {
                continue;
            }

            $quantityToShip = $lines[$line->id];

            if ($line->shippableQuantity < $quantityToShip) {
                return false;
            }
        }

        return true;
    }

    /**
     * This code checks if all products in the order are going to be shipped. This used the qty_shipped column
     * so it works with partial shipments as well.
     * Examples:
     * - You have an order with 2 items. You are shipping both items. This function will return true.
     * - You have an order with 2 items. The first shipments contains 1 items, the second shipment also. The first
     *   time this function returns false, the second time true as it is shipping all remaining items.
     *
     * @param Order $order
     * @param Order\Shipment $shipment
     * @return bool
     */
    private function isShippingAllItems(Order $order, Order\Shipment $shipment)
    {
        /**
         * First build an array of all products in the order like this:
         * [item ID => quantiy]
         * [123 => 2]
         * [124 => 1]
         *
         * The method `getOrigData('qty_shipped')` is used as the value of `getQtyShipped()` is somewhere adjusted
         * and invalid, so not reliable to use for our case.
         */
        $shippableOrderItems = [];
        /** @var Order\Item $item */
        foreach ($order->getAllVisibleItems() as $item) {
            if ($item->getProducttype() != ProductType::TYPE_BUNDLE || !$item->isShipSeparately()) {
                $quantity = $item->getQtyOrdered() - $item->getOrigData('qty_shipped');
                $shippableOrderItems[$item->getId()] = $quantity;
                continue;
            }

            /** @var Order\Item $childItem */
            foreach ($item->getChildrenItems() as $childItem) {
                if ((float)$childItem->getQtyShipped() === (float)$childItem->getOrigData('qty_shipped')) {
                    continue;
                }

                $quantity = $childItem->getQtyOrdered() - $childItem->getOrigData('qty_shipped');
                $shippableOrderItems[$childItem->getId()] = $quantity;
            }
        }

        /**
         * Now subtract the number of items to ship in this shipment.
         *
         * Before:
         * [123 => 2]
         *
         * Shipping 1 item
         *
         * After:
         * [123 => 1]
         */
        /** @var Order\Shipment\Item $item */
        foreach ($shipment->getAllItems() as $item) {
            if ($item->getOrderItem()->getProductType() == ProductType::TYPE_BUNDLE &&
                $item->getOrderItem()->isShipSeparately()
            ) {
                continue;
            }

            $shippableOrderItems[$item->getOrderItemId()] -= $item->getQty();
        }

        /**
         * Count the total number of items in the array. If it equals 0 then all (remaining) items in the order
         * are shipped.
         */
        return array_sum($shippableOrderItems) == 0;
    }

    /**
     * @param OrderInterface $order
     * @param InvoiceInterface|null $invoice
     * @return double
     */
    private function getCaptureAmount(OrderInterface $order, InvoiceInterface $invoice = null)
    {
        if ($invoice) {
            return $invoice->getBaseGrandTotal();
        }

        $payment = $order->getPayment();
        if ($invoice = $payment->getCreatedInvoice()) {
            return $invoice->getBaseGrandTotal();
        }

        return $order->getBaseGrandTotal();
    }
}
