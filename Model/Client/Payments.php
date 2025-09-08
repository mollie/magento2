<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Client;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address;
use Magento\Sales\Model\OrderRepository;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Payment as MolliePayment;
use Mollie\Api\Types\PaymentStatus;
use Mollie\Payment\Exceptions\PaymentAborted;
use Mollie\Payment\Helper\General as MollieHelper;
use Mollie\Payment\Model\Client\Payments\ProcessTransaction;
use Mollie\Payment\Service\Mollie\DashboardUrl;
use Mollie\Payment\Service\Mollie\Order\CanRegisterCaptureNotification;
use Mollie\Payment\Service\Mollie\Order\GetTransactionId;
use Mollie\Payment\Service\Mollie\Order\LinkTransactionToOrder;
use Mollie\Payment\Service\Mollie\TransactionDescription;
use Mollie\Payment\Service\Mollie\ValidateMetadata;
use Mollie\Payment\Service\Order\BuildTransaction;
use Mollie\Payment\Service\Order\CancelOrder;
use Mollie\Payment\Service\Order\ExpiredOrderToTransaction;
use Mollie\Payment\Service\Order\MethodCode;
use Mollie\Payment\Service\Order\OrderAmount;
use Mollie\Payment\Service\Order\OrderCommentHistory;
use Mollie\Payment\Service\Order\SaveAdditionalInformationDetails;
use Mollie\Payment\Service\Order\SendOrderEmails;
use Mollie\Payment\Service\Order\Transaction;
use Mollie\Payment\Service\Order\TransactionProcessor;
use Mollie\Payment\Service\PaymentToken\PaymentTokenForOrder;

/**
 * Class Payments
 *
 * @package Mollie\Payment\Model\Client
 */
class Payments extends AbstractModel
{
    const CHECKOUT_TYPE = 'payment';
    const TRANSACTION_TYPE_WEBHOOK = 'webhook';
    const TRANSACTION_TYPE_SUBSCRIPTION = 'subscription';

    /**
     * @var MollieHelper
     */
    private $mollieHelper;
    /**
     * @var OrderRepository
     */
    private $orderRepository;
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;
    /**
     * @var OrderCommentHistory
     */
    private $orderCommentHistory;
    /**
     * @var BuildTransaction
     */
    private $buildTransaction;
    /**
     * @var DashboardUrl
     */
    private $dashboardUrl;
    /**
     * @var Transaction
     */
    private $transaction;
    /**
     * @var TransactionProcessor
     */
    private $transactionProcessor;

    /**
     * @var EventManager
     */
    private $eventManager;

    /**
     * @var OrderAmount
     */
    private $orderAmount;

    /**
     * @var TransactionDescription
     */
    private $transactionDescription;

    /**
     * @var CancelOrder
     */
    private $cancelOrder;

    /**
     * @var PaymentTokenForOrder
     */
    private $paymentTokenForOrder;

    /**
     * @var SendOrderEmails
     */
    private $sendOrderEmails;

    /**
     * @var LinkTransactionToOrder
     */
    private $linkTransactionToOrder;

    /**
     * @var Payments\ProcessTransaction
     */
    private $processTransaction;

    /**
     * @var ValidateMetadata
     */
    private $validateMetadata;

    /**
     * @var SaveAdditionalInformationDetails
     */
    private $saveAdditionalInformationDetails;

    /**
     * @var ExpiredOrderToTransaction
     */
    private $expiredOrderToTransaction;

    /**
     * @var CanRegisterCaptureNotification
     */
    private $canRegisterCaptureNotification;
    /**
     * @var MethodCode
     */
    private $methodCode;
    /**
     * @var GetTransactionId
     */
    private $getTransactionId;

    public function __construct(
        OrderRepository $orderRepository,
        CheckoutSession $checkoutSession,
        MollieHelper $mollieHelper,
        OrderCommentHistory $orderCommentHistory,
        BuildTransaction $buildTransaction,
        DashboardUrl $dashboardUrl,
        Transaction $transaction,
        TransactionProcessor $transactionProcessor,
        OrderAmount $orderAmount,
        TransactionDescription $transactionDescription,
        CancelOrder $cancelOrder,
        PaymentTokenForOrder $paymentTokenForOrder,
        SendOrderEmails $sendOrderEmails,
        EventManager $eventManager,
        LinkTransactionToOrder $linkTransactionToOrder,
        ProcessTransaction $processTransaction,
        ValidateMetadata $validateMetadata,
        SaveAdditionalInformationDetails $saveAdditionalInformationDetails,
        ExpiredOrderToTransaction $expiredOrderToTransaction,
        CanRegisterCaptureNotification $canRegisterCaptureNotification,
        MethodCode $methodCode,
        GetTransactionId $getTransactionId
    ) {
        $this->orderRepository = $orderRepository;
        $this->checkoutSession = $checkoutSession;
        $this->mollieHelper = $mollieHelper;
        $this->orderCommentHistory = $orderCommentHistory;
        $this->buildTransaction = $buildTransaction;
        $this->dashboardUrl = $dashboardUrl;
        $this->transaction = $transaction;
        $this->transactionProcessor = $transactionProcessor;
        $this->eventManager = $eventManager;
        $this->orderAmount = $orderAmount;
        $this->transactionDescription = $transactionDescription;
        $this->cancelOrder = $cancelOrder;
        $this->paymentTokenForOrder = $paymentTokenForOrder;
        $this->sendOrderEmails = $sendOrderEmails;
        $this->linkTransactionToOrder = $linkTransactionToOrder;
        $this->processTransaction = $processTransaction;
        $this->validateMetadata = $validateMetadata;
        $this->saveAdditionalInformationDetails = $saveAdditionalInformationDetails;
        $this->expiredOrderToTransaction = $expiredOrderToTransaction;
        $this->canRegisterCaptureNotification = $canRegisterCaptureNotification;
        $this->methodCode = $methodCode;
        $this->getTransactionId = $getTransactionId;
    }

    /**
     * @param OrderInterface $order
     * @param \Mollie\Api\MollieApiClient $mollieApi
     *
     * @return string
     * @throws \Mollie\Api\Exceptions\ApiException
     */
    public function startTransaction(OrderInterface $order, $mollieApi)
    {
        $storeId = $order->getStoreId();
        $orderId = $order->getEntityId();

        $transactionId = $order->getMollieTransactionId();
        if (!empty($transactionId) &&
            substr($transactionId, 0, 4) != 'ord_' &&
            $checkoutUrl = $this->getCheckoutUrl($mollieApi, $transactionId)
        ) {
            return $checkoutUrl;
        }

        $paymentToken = $this->paymentTokenForOrder->execute($order);
        $method = $this->methodCode->execute($order);

        $paymentData = [
            'amount'         => $this->mollieHelper->getOrderAmountByOrder($order),
            'description'    => $this->transactionDescription->forRegularTransaction($order),
            'billingAddress' => $this->getAddressLine($order->getBillingAddress()),
            'redirectUrl'    => $this->transaction->getRedirectUrl($order, $paymentToken),
            'webhookUrl'     => $this->transaction->getWebhookUrl([$order]),
            'method'         => $method,
            'metadata'       => [
                'order_id'      => $orderId,
                'store_id'      => $order->getStoreId(),
                'payment_token' => $paymentToken
            ],
            'locale'         => $this->mollieHelper->getLocaleCode($storeId, self::CHECKOUT_TYPE)
        ];

        if (!$order->getIsVirtual() && $order->hasData('shipping_address_id')) {
            $paymentData['shippingAddress'] = $this->getAddressLine($order->getShippingAddress());
        }

        if ($method == 'banktransfer') {
            $paymentData['dueDate'] = $this->mollieHelper->getBanktransferDueDate($storeId);
        }

        if (!$paymentData['billingAddress']['email']) {
            $paymentData['billingAddress']['email'] = $order->getCustomerEmail();
        }

        $paymentData = $this->buildTransaction->execute($order, static::CHECKOUT_TYPE, $paymentData);

        $paymentData = $this->mollieHelper->validatePaymentData($paymentData);
        $this->mollieHelper->addTolog('request', $paymentData);
        $payment = $mollieApi->payments->create($paymentData);
        $this->processResponse($order, $payment);

        // Order is paid immediately (eg. Credit Card with Components, Apple Pay), process transaction
        if ($payment->isAuthorized() || $payment->isPaid()) {
            $this->processTransaction->execute($order, static::TRANSACTION_TYPE_WEBHOOK);
        }

        return $payment->getCheckoutUrl();
    }

    public function getAddressLine(OrderAddressInterface $address): array
    {
        $output = [
            'streetAndNumber' => rtrim(implode(' ', $address->getStreet()), ' '),
            'postalCode' => $address->getPostcode(),
            'city' => $address->getCity(),
            'region' => $address->getRegion(),
            'country' => $address->getCountryId(),
            'email' => $address->getEmail(),
        ];

        if ($address->getAddressType() == Address::TYPE_BILLING) {
            $output['givenName'] = $address->getFirstname();
            $output['familyName'] = $address->getLastname();
        }

        return $output;
    }

    /**
     * @param OrderInterface $order
     * @param MolliePayment $payment
     */
    public function processResponse(OrderInterface $order, $payment)
    {
        $eventData = [
            'order' => $order,
            'mollie_payment' => $payment,
        ];

        $this->eventManager->dispatch('mollie_process_response', $eventData);
        $this->eventManager->dispatch('mollie_process_response_payments_api', $eventData);
        $this->mollieHelper->addTolog('response', $payment);

        // The order is canceled before but now restarted, so uncancel the order.
        if ($order->getState() == Order::STATE_CANCELED) {
            $this->mollieHelper->uncancelOrder($order);
        }

        $order->getPayment()->setAdditionalInformation('checkout_url', $payment->getCheckoutUrl());
        $order->getPayment()->setAdditionalInformation('checkout_type', self::CHECKOUT_TYPE);
        $order->getPayment()->setAdditionalInformation('payment_status', $payment->status);
        if (isset($payment->expiresAt)) {
            $order->getPayment()->setAdditionalInformation('expires_at', $payment->expiresAt);
        }

        if (isset($payment->_links->changePaymentState->href)) {
            $order->getPayment()->setAdditionalInformation(
                'mollie_change_payment_state_url',
                $payment->_links->changePaymentState->href
            );
        }

        $message = __('Customer redirected to Mollie');
        if ($order->getPayment()->getMethodInstance()->getCode() == 'mollie_methods_paymentlink') {
            $message = __('Created Mollie Checkout Url');
        }

        $status = $this->mollieHelper->getPendingPaymentStatus($order);
        $order->addStatusToHistory($status, $message, false);
        $order->setState(Order::STATE_PENDING_PAYMENT);
        $this->linkTransactionToOrder->execute($payment->id, $order);
        $this->orderRepository->save($order);
    }

    /**
     * @param Order                       $order
     * @param \Mollie\Api\MollieApiClient $mollieApi
     * @param string                      $type
     * @param null                        $paymentToken
     *
     * @return array
     * @throws LocalizedException
     * @throws \Mollie\Api\Exceptions\ApiException
     */
    public function processTransaction(Order $order, $mollieApi, $type = 'webhook', $paymentToken = null)
    {
        $orderId = $order->getId();
        $storeId = $order->getStoreId();
        $transactionId = $this->getTransactionId->forOrder($order);
        $paymentData = $mollieApi->payments->get($transactionId);

        $this->validateMetadata->execute($paymentData->metadata, $order);

        $this->mollieHelper->addTolog($type, $paymentData);
        $dashboardUrl = $this->dashboardUrl->forPaymentsApi($order->getStoreId(), $paymentData->id);
        $order->getPayment()->setAdditionalInformation('dashboard_url', $dashboardUrl);
        $order->getPayment()->setAdditionalInformation('mollie_id', $paymentData->id);
        $order->getPayment()->setAdditionalInformation('method', $paymentData->method);

        $status = $paymentData->status;
        $payment = $order->getPayment();
        if (in_array($type, [static::TRANSACTION_TYPE_WEBHOOK, static::TRANSACTION_TYPE_SUBSCRIPTION]) &&
            $payment->getAdditionalInformation('payment_status') != $status
        ) {
            $payment->setAdditionalInformation('payment_status', $status);
            $this->orderRepository->save($order);
        }

        $refunded = isset($paymentData->_links->refunds) ? true : false;

        if (in_array($status, ['paid', 'authorized']) && !$refunded) {
            $amount = $paymentData->amount->value;
            $currency = $paymentData->amount->currency;
            $orderAmount = $this->orderAmount->getByTransactionId($transactionId);
            if ($currency != $orderAmount['currency']) {
                $msg = ['success' => false, 'status' => 'paid', 'order_id' => $orderId, 'type' => $type];
                $this->mollieHelper->addTolog('error', __('Currency does not match.'));
                return $msg;
            }
            if ($paymentData->details !== null) {
                $this->saveAdditionalInformationDetails->execute($payment, $paymentData->details);
            }

            if (!$payment->getIsTransactionClosed() &&
                in_array($type, [static::TRANSACTION_TYPE_WEBHOOK, static::TRANSACTION_TYPE_SUBSCRIPTION])
            ) {
                if ($order->isCanceled()) {
                    $order = $this->mollieHelper->uncancelOrder($order);
                }

                if (abs($amount - $orderAmount['value']) < 0.01 ||
                    $type == static::TRANSACTION_TYPE_SUBSCRIPTION
                ) {
                    $payment->setTransactionId($transactionId);
                    $payment->setCurrencyCode($order->getBaseCurrencyCode());
                    $payment->setIsTransactionClosed(true);

                    if ($paymentData->hasChargebacks()) {
                        $order->addCommentToStatusHistory(
                            __(
                                'Mollie: Received a chargeback with an amount of %1',
                                $order->getBaseCurrency()->formatTxt($paymentData->getAmountChargedBack())
                            )
                        );

                        $msg = ['success' => true, 'status' => 'paid', 'order_id' => $orderId, 'type' => $type];
                        $this->mollieHelper->addTolog('success', $msg);
                        $this->checkCheckoutSession($order, $paymentToken, $paymentData, $type);
                        return $msg;
                    }

                    if ($this->canRegisterCaptureNotification->execute($order, $paymentData) ||
                        $type != static::TRANSACTION_TYPE_WEBHOOK
                    ) {
                        if ($paymentData->getAmountCaptured() != 0.0) {
                            $order->addCommentToStatusHistory(
                                __(
                                    'Successfully captured an amount of %1.',
                                    $order->getBaseCurrency()->formatTxt($paymentData->getAmountCaptured())
                                )
                            );
                        }

                        if (!in_array($order->getState(), [Order::STATE_PROCESSING, Order::STATE_COMPLETE])) {
                            $payment->registerCaptureNotification($order->getBaseGrandTotal(), true);
                        }
                    }

                    $order->setState(Order::STATE_PROCESSING);
                    $this->transactionProcessor->process($order, null, $paymentData);

                    if ($paymentData->settlementAmount !== null) {
                        if ($paymentData->amount->currency != $paymentData->settlementAmount->currency) {
                            $message = __(
                                'Mollie: Captured %1, Settlement Amount %2',
                                $paymentData->amount->currency . ' ' . $paymentData->amount->value,
                                $paymentData->settlementAmount->currency . ' ' . $paymentData->settlementAmount->value
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
                    $this->sendOrderEmails->sendOrderConfirmation($order);
                }

                if ($invoice && !$invoice->getEmailSent() && $sendInvoice) {
                    $this->sendOrderEmails->sendInvoiceEmail($invoice);
                }
            }

            $msg = ['success' => true, 'status' => 'paid', 'order_id' => $orderId, 'type' => $type];
            $this->mollieHelper->addTolog('success', $msg);
            $this->checkCheckoutSession($order, $paymentToken, $paymentData, $type);
            return $msg;
        }
        if ($refunded) {
            $msg = ['success' => true, 'status' => 'refunded', 'order_id' => $orderId, 'type' => $type];
            $this->mollieHelper->addTolog('success', $msg);
            return $msg;
        }
        if ($status == 'open') {
            if ($paymentData->method == 'banktransfer' && !$order->getEmailSent()) {
                if (!$statusPending = $this->mollieHelper->getStatusPendingBanktransfer($storeId)) {
                    $statusPending = $order->getStatus();
                }

                $order->setStatus($statusPending);
                $order->setState(Order::STATE_PENDING_PAYMENT);
                $this->sendOrderEmails->sendOrderConfirmation($order);

                $this->transactionProcessor->process($order, null, $paymentData);
                $this->orderRepository->save($order);
            }
            $msg = ['success' => true, 'status' => 'open', 'order_id' => $orderId, 'type' => $type];
            $this->mollieHelper->addTolog('success', $msg);
            $this->checkCheckoutSession($order, $paymentToken, $paymentData, $type);
            return $msg;
        }
        if ($status == 'pending') {
            $msg = ['success' => true, 'status' => 'pending', 'order_id' => $orderId, 'type' => $type];
            $this->mollieHelper->addTolog('success', $msg);
            return $msg;
        }
        if ($status == 'expired') {
            if ($this->expiredOrderToTransaction->hasMultipleTransactions($order)) {
                $this->expiredOrderToTransaction->markTransactionAsSkipped($transactionId);
                $msg = ['success' => false, 'status' => $status, 'order_id' => $orderId, 'type' => $type];
                $this->mollieHelper->addTolog('success', $msg);
                return $msg;
            }
        }
        if ($status == 'canceled' || $status == 'failed' || $status == 'expired') {
            if (in_array($type, [static::TRANSACTION_TYPE_WEBHOOK, static::TRANSACTION_TYPE_SUBSCRIPTION])) {
                $this->cancelOrder->execute($order, $status);
                $this->transactionProcessor->process($order, null, $paymentData);
            }

            $msg = ['success' => false, 'status' => $status, 'order_id' => $orderId, 'type' => $type];
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
        $paymentData = $mollieApi->payments->get($transactionId);

        $mapping = [
            PaymentStatus::STATUS_OPEN => Order::STATE_NEW,
            PaymentStatus::STATUS_PENDING => Order::STATE_PENDING_PAYMENT,
            PaymentStatus::STATUS_AUTHORIZED => Order::STATE_PROCESSING,
            PaymentStatus::STATUS_CANCELED => Order::STATE_CANCELED,
            PaymentStatus::STATUS_EXPIRED => Order::STATE_CLOSED,
            PaymentStatus::STATUS_PAID => Order::STATE_PROCESSING,
            PaymentStatus::STATUS_FAILED => Order::STATE_CANCELED,
        ];

        $expectedStatus = $mapping[$paymentData->status];

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
        if (in_array($type, [static::TRANSACTION_TYPE_WEBHOOK, static::TRANSACTION_TYPE_SUBSCRIPTION])) {
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

    private function getCheckoutUrl(MollieApiClient $mollieApi, ?string $transactionId): ?string
    {
        if ($transactionId === null) {
            return null;
        }

        $payment = $mollieApi->payments->get($transactionId);
        if ($payment->status == 'paid') {
            throw new PaymentAborted(__('This order already has been paid.'));
        }

        return $payment->getCheckoutUrl();
    }
}
