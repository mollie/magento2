<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Client;

use Magento\Catalog\Model\Product\Type as ProductType;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Sales\Api\Data\InvoiceInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\ResourceModel\Order\Handler\State;
use Magento\Checkout\Model\Session as CheckoutSession;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Order as MollieOrder;
use Mollie\Api\Resources\Payment;
use Mollie\Api\Types\OrderStatus;
use Mollie\Payment\Config;
use Mollie\Payment\Exceptions\PaymentAborted;
use Mollie\Payment\Helper\General as MollieHelper;
use Mollie\Payment\Model\Adminhtml\Source\InvoiceMoment;
use Mollie\Payment\Model\Client\Orders\ProcessTransaction;
use Mollie\Payment\Model\OrderLines;
use Mollie\Payment\Service\Mollie\Order\LinkTransactionToOrder;
use Mollie\Payment\Service\Mollie\Order\RefundUsingPayment;
use Mollie\Payment\Service\Mollie\Order\Transaction\Expires;
use Mollie\Payment\Service\Order\BuildTransaction;
use Mollie\Payment\Service\Order\Invoice\ShouldEmailInvoice;
use Mollie\Payment\Service\Order\Lines\Order as OrderOrderLines;
use Mollie\Payment\Service\Order\Lines\StoreCredit;
use Mollie\Payment\Service\Order\MethodCode;
use Mollie\Payment\Service\Order\OrderCommentHistory;
use Mollie\Payment\Service\Order\PartialInvoice;
use Mollie\Payment\Service\Order\ProcessAdjustmentFee;
use Mollie\Payment\Service\Order\Transaction;
use Mollie\Payment\Service\PaymentToken\PaymentTokenForOrder;

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
     * @var OrderOrderLines
     */
    private $orderOrderLines;
    /**
     * @var OrderRepository
     */
    private $orderRepository;
    /**
     * @var InvoiceSender
     */
    private $invoiceSender;
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
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var PaymentTokenForOrder
     */
    private $paymentTokenForOrder;

    /**
     * @var ProcessTransaction
     */
    private $processTransaction;

    /**
     * @var \Mollie\Payment\Service\Mollie\MollieApiClient
     */
    private $mollieApiClient;

    /**
     * @var Config
     */
    private $config;

    /**
     * @var LinkTransactionToOrder
     */
    private $linkTransactionToOrder;
    /**
     * @var ShouldEmailInvoice
     */
    private $shouldEmailInvoice;
    /**
     * @var MethodCode
     */
    private $methodCode;

    public function __construct(
        OrderLines $orderLines,
        OrderOrderLines $orderOrderLines,
        InvoiceSender $invoiceSender,
        OrderRepository $orderRepository,
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
        PaymentTokenForOrder $paymentTokenForOrder,
        ProcessTransaction $processTransaction,
        \Mollie\Payment\Service\Mollie\MollieApiClient $mollieApiClient,
        Config $config,
        EventManager $eventManager,
        LinkTransactionToOrder $linkTransactionToOrder,
        ShouldEmailInvoice $shouldEmailInvoice,
        MethodCode $methodCode
    ) {
        $this->orderLines = $orderLines;
        $this->orderOrderLines = $orderOrderLines;
        $this->invoiceSender = $invoiceSender;
        $this->orderRepository = $orderRepository;
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
        $this->eventManager = $eventManager;
        $this->paymentTokenForOrder = $paymentTokenForOrder;
        $this->processTransaction = $processTransaction;
        $this->mollieApiClient = $mollieApiClient;
        $this->config = $config;
        $this->linkTransactionToOrder = $linkTransactionToOrder;
        $this->shouldEmailInvoice = $shouldEmailInvoice;
        $this->methodCode = $methodCode;
    }

    /**
     * @param OrderInterface $order
     * @param MollieApiClient $mollieApi
     *
     * @return string
     * @throws LocalizedException
     * @throws ApiException
     */
    public function startTransaction(OrderInterface $order, $mollieApi)
    {
        $storeId = $order->getStoreId();
        $orderId = $order->getEntityId();
        $additionalData = $order->getPayment()->getAdditionalInformation();

        $transactionId = $order->getMollieTransactionId();
        if (!empty($transactionId) &&
            $checkoutUrl = $this->getCheckoutUrl($mollieApi, $order)
        ) {
            return $checkoutUrl;
        }

        $paymentToken = $this->paymentTokenForOrder->execute($order);
        $method = $this->methodCode->execute($order);
        $method = str_replace('_vault', '', $method);
        $orderData = [
            'amount'              => $this->mollieHelper->getOrderAmountByOrder($order),
            'orderNumber'         => $order->getIncrementId(),
            'billingAddress'      => $this->getAddressLine($order->getBillingAddress()),
            'consumerDateOfBirth' => null,
            'lines'               => $this->orderOrderLines->get($order),
            'redirectUrl'         => $this->transaction->getRedirectUrl($order, $paymentToken),
            'webhookUrl'          => $this->transaction->getWebhookUrl([$order]),
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

        if ($method == 'banktransfer') {
            $orderData['payment']['dueDate'] = $this->mollieHelper->getBanktransferDueDate($storeId);
        }

        if ($this->expires->availableForMethod($this->methodCode->getExpiresAtMethod(), $storeId)) {
            $orderData['expiresAt'] = $this->expires->atDateForMethod(
                $this->methodCode->getExpiresAtMethod(),
                $storeId
            );
        }

        $orderData = $this->buildTransaction->execute($order, static::CHECKOUT_TYPE, $orderData);

        $this->mollieHelper->addTolog('request', $orderData);
        $mollieOrder = $mollieApi->orders->create($orderData, ['embed' => 'payments']);
        $this->processResponse($order, $mollieOrder);

        // Order is paid immediately (eg. Credit Card with Components, Apple Pay), process transaction
        if ($mollieOrder->isAuthorized() || $mollieOrder->isPaid()) {
            $this->processTransaction->execute($order, 'webhook');
        }

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
            'title'            => trim($address->getPrefix() ?? ''),
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
     * @param MollieOrder $mollieOrder
     *
     * @throws LocalizedException
     */
    public function processResponse(OrderInterface $order, MollieOrder $mollieOrder): void
    {
        $eventData = [
            'order' => $order,
            'mollie_order' => $mollieOrder,
        ];

        $this->eventManager->dispatch('mollie_process_response', $eventData);
        $this->eventManager->dispatch('mollie_process_response_orders_api', $eventData);

        $this->mollieHelper->addTolog('response', $mollieOrder);
        $order->getPayment()->setAdditionalInformation('checkout_url', $mollieOrder->getCheckoutUrl());
        $order->getPayment()->setAdditionalInformation('checkout_type', self::CHECKOUT_TYPE);
        $order->getPayment()->setAdditionalInformation('payment_status', $mollieOrder->status);
        if (isset($mollieOrder->expiresAt)) {
            $order->getPayment()->setAdditionalInformation('expires_at', $mollieOrder->expiresAt);
        }

        if (isset($mollieOrder->_links->changePaymentState->href)) {
            $order->getPayment()->setAdditionalInformation(
                'mollie_change_payment_state_url',
                $mollieOrder->_links->changePaymentState->href
            );
        }

        $this->orderLines->linkOrderLines($mollieOrder->lines, $order);

        $status = $this->mollieHelper->getPendingPaymentStatus($order);

        $msg = __('Customer redirected to Mollie');
        if ($order->getPayment()->getMethodInstance()->getCode() == 'mollie_methods_paymentlink') {
            $msg = __('Created Mollie Checkout Url');
        }

        $order->setState(Order::STATE_PENDING_PAYMENT);
        $order->addStatusToHistory($status, $msg, false);
        $this->linkTransactionToOrder->execute($mollieOrder->id, $order);
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
     * @deprecated since 3.0.0
     */
    public function processTransaction(Order $order, $mollieApi, $type = 'webhook', $paymentToken = null)
    {
        $result = $this->processTransaction->execute($order, $type);

        return $result->toArray();
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
            if (!in_array($mollieOrder->status, [OrderStatus::STATUS_EXPIRED, OrderStatus::STATUS_CANCELED])) {
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
        return $this->mollieApiClient->loadByApiKey($apiKey);
    }

    /**
     * @param Order\Shipment $shipment
     * @param OrderInterface $order
     *
     * @return $this
     * @throws LocalizedException
     */
    public function createShipment(Order\Shipment $shipment, OrderInterface $order)
    {
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

        try {
            $mollieApi = $this->loadMollieApi($apiKey);
            $mollieOrder = $mollieApi->orders->get($transactionId, ['embed' => 'payments']);

            if ($mollieOrder->status == 'completed') {
                $this->messageManager->addWarningMessage(
                    __('All items in this order where already marked as shipped in the Mollie dashboard.')
                );
                return $this;
            }

            if ($this->isShippingAllItems($order, $shipment)) {
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

            // @phpstan-ignore-next-line
            $mollieShipmentId = isset($mollieShipment) ? $mollieShipment->id : 0;
            $shipment->setMollieShipmentId($mollieShipmentId);

            /**
             * Check if Transactions needs to be captured (eg. Klarna methods)
             */
            $payment = $order->getPayment();
            if (!$payment->getIsTransactionClosed()) {
                $invoice = $order->getInvoiceCollection()->getLastItem();
                if ($this->mollieHelper->getInvoiceMoment($order->getStoreId()) == InvoiceMoment::ON_SHIPMENT) {
                    $invoice = $this->partialInvoice->createFromShipment($shipment);
                }

                $captureAmount = $this->getCaptureAmount($order, $invoice && $invoice->getId() ? $invoice : null);

                $payments = $mollieOrder->_embedded->payments;
                $paymentId = end($payments)->id;

                $payment->setTransactionId($paymentId . '-' . $shipment->getMollieShipmentId());
                $payment->registerCaptureNotification($captureAmount, true);

                if ($invoice) {
                    $invoice->setState(Invoice::STATE_PAID);
                }

                $this->orderRepository->save($order);

                $sendInvoice = $this->shouldEmailInvoice->execute((int)$order->getStoreId(), $payment->getMethod());
                if ($invoice && $invoice->getId() && !$invoice->getEmailSent() && $sendInvoice) {
                    $this->invoiceSender->send($invoice);
                    $message = __('Notified customer about invoice #%1', $invoice->getIncrementId());
                    $this->orderCommentHistory->add($order, $message, true);
                }
            }
        } catch (\Exception $e) {
            $message = __(
                'Unable to ship order "%1" due to error: %2',
                $order->getIncrementId(),
                $e->getMessage()
            );

            $this->mollieHelper->addTolog('error', $message);
            throw new LocalizedException(
                $message
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

        $methodCode = $this->methodCode->execute($order);
        $methods = ['klarna', 'klarnapaylater', 'klarnasliceit', 'klarnapaynow'];
        if (!$order->hasShipments() && (in_array($methodCode, $methods))) {
            $msg = __('Order can only be refunded after Klarna has been captured (after shipment)');
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

        /** @var int|float $remainderAmount */
        $remainderAmount = $order->getPayment()->getAdditionalInformation('remainder_amount');
        $grandTotal = $this->config->useBaseCurrency($storeId) ?
            $creditmemo->getBaseGrandTotal() :
            $creditmemo->getGrandTotal();
        $maximumAmountToRefund = $order->getBaseGrandTotal() - $remainderAmount;
        if ($remainderAmount) {
            $this->config->addToLog('Refunding order using remainder amount', ['order_id' => $order->getId()]);
            $amount = $grandTotal > $maximumAmountToRefund ? $maximumAmountToRefund : $grandTotal;

            $this->refundUsingPayment->execute(
                $mollieApi,
                $transactionId,
                'EUR',
                $amount
            );

            $order->setState(Order::STATE_CLOSED);

            return $this;
        }

        if ($this->storeCredit->creditmemoHasStoreCredit($creditmemo)) {
            $this->config->addToLog('Refunding order using store credit', ['order_id' => $order->getId()]);

            $this->refundUsingPayment->execute(
                $mollieApi,
                $transactionId,
                $creditmemo->getOrderCurrencyCode(),
                $grandTotal
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
        $addShippingToRefund = false;
        $shippingCostsLine = $this->orderLines->getShippingFeeItemLineOrder($orderId);
        if ($shippingCostsLine->getId() && $shippingCostsLine->getQtyRefunded() == 0) {
            if ($creditmemo->getShippingAmount() > 0) {
                $addShippingToRefund = true;
                $this->config->addToLog('Add shipping to refund', ['order_id' => $order->getId()]);
                if (abs($creditmemo->getShippingInclTax() - $shippingCostsLine->getTotalAmount()) > 0.01) {
                    $msg = __('Unable to create online refund, as shipping costs do not match');
                    $this->mollieHelper->addTolog('error', $msg);
                    throw new LocalizedException($msg);
                }
            }
        }

        $shouldRefund = $addShippingToRefund || $creditmemo->getAllItems();
        if (!$shouldRefund || $this->adjustmentFee->doNotRefundInMollie()) {
            return $this;
        }

        try {
            /**
             * Sometimes we don't get the correct state when working with bundles, so manually check it.
             */
            $this->orderState->check($order);
            $mollieOrder = $mollieApi->orders->get($transactionId, ['embed' => 'payments']);

            /** @var Payment $payment */
            $payment = $mollieOrder->payments()->offsetGet(0);
            $metadata = $payment->metadata ?? new \stdClass();
            $metadata->refunded = true;
            $payment->metadata = $metadata;
            $payment->update();

            if ($order->getState() == Order::STATE_CLOSED) {
                $this->config->addToLog('Refunding all open items', ['order_id' => $order->getId()]);
                $mollieOrder->refundAll();
            } else {
                $orderLines = $this->orderLines->getCreditmemoOrderLines($creditmemo, $addShippingToRefund);
                $this->config->addToLog('Partially refunding order', [
                    'order_id' => $order->getId(),
                    'order_lines' => $orderLines,
                ]);
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
     * @param MollieOrder $mollieOrder
     * @param $orderLines
     * @return bool
     */
    private function itemsAreShippable(MollieOrder $mollieOrder, $orderLines)
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
            if (($item->getProducttype() != ProductType::TYPE_BUNDLE ||
                !$item->isShipSeparately()) &&
                !$item->getIsVirtual()
            ) {
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
            /**
             * Some extensions create shipments for all items, but that causes problems, so ignore them.
             */
            if (!isset($shippableOrderItems[$item->getOrderItemId()])) {
                continue;
            }

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
    private function getCaptureAmount(OrderInterface $order, ?InvoiceInterface $invoice = null)
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

    private function getCheckoutUrl(MollieApiClient $mollieApi, OrderInterface $order): string
    {
        $mollieOrder = $mollieApi->orders->get($order->getMollieTransactionId());
        if ($checkoutUrl = $mollieOrder->getCheckoutUrl()) {
            return $checkoutUrl;
        }

        if ($mollieOrder->status == 'paid') {
            $this->config->addToLog('error', [
                'message' => 'This order already has been paid.',
                'order' => $order->getEntityId(),
            ]);

            throw new PaymentAborted(__('This order already has been paid.'));
        }

        // There is no checkout URL, the transaction is either canceled or expired. Create a new transaction.
        $order->setMollieTransactionId(null);
        return $this->startTransaction($order, $mollieApi);
    }
}
