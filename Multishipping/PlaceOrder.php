<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Multishipping;

use Magento\Multishipping\Model\Checkout\Type\Multishipping\PlaceOrderInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Model\Client\Payments;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Service\Mollie\TransactionDescription;
use Mollie\Payment\Service\Order\BuildTransaction;
use Mollie\Payment\Service\Order\Transaction;

class PlaceOrder implements PlaceOrderInterface
{
    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;

    /**
     * @var Mollie
     */
    private $mollieModel;

    /**
     * @var Payments
     */
    private $molliePaymentsApi;

    /**
     * @var array
     */
    private $errorList = [];

    /**
     * @var General
     */
    private $mollieHelper;

    /**
     * @var Transaction
     */
    private $transaction;

    /**
     * @var BuildTransaction
     */
    private $buildTransaction;

    /**
     * @var CheckoutUrl
     */
    private $checkoutUrl;

    /**
     * @var TransactionDescription
     */
    private $transactionDescription;

    public function __construct(
        OrderManagementInterface $orderManagement,
        Mollie $mollieModel,
        Payments $molliePaymentsApi,
        General $mollieHelper,
        Transaction $transaction,
        BuildTransaction $buildTransaction,
        CheckoutUrl $checkoutUrl,
        TransactionDescription $transactionDescription
    ) {
        $this->orderManagement = $orderManagement;
        $this->mollieModel = $mollieModel;
        $this->molliePaymentsApi = $molliePaymentsApi;
        $this->mollieHelper = $mollieHelper;
        $this->transaction = $transaction;
        $this->buildTransaction = $buildTransaction;
        $this->checkoutUrl = $checkoutUrl;
        $this->transactionDescription = $transactionDescription;
    }

    /**
     * @param OrderInterface[] $orderList
     * @return array
     */
    public function place(array $orderList): array
    {
        try {
            foreach ($orderList as $order) {
                $this->orderManagement->place($order);
            }

            $firstOrder = reset($orderList);
            $storeId = $firstOrder->getStoreId();
            $paymentData = $this->buildPaymentData($orderList, $storeId);

            $paymentData = $this->mollieHelper->validatePaymentData($paymentData);
            $this->mollieHelper->addTolog('request', $paymentData);

            $mollieApi = $this->mollieModel->getMollieApi($storeId);
            $paymentResponse = $mollieApi->payments->create($paymentData);

            if ($url = $paymentResponse->getCheckoutUrl()) {
                $this->checkoutUrl->setUrl($url);
            }
        } catch (\Exception $exception) {
            $errorList = [];
            foreach ($orderList as $order) {
                $errorList[$order->getIncrementId()] = $exception;
            }

            return $errorList;
        }

        foreach ($orderList as $order) {
            try {
                $this->molliePaymentsApi->processResponse($order, $paymentResponse);
            } catch (\Exception $exception) {
                $this->errorList[$order->getIncrementId()] = $exception;
            }
        }

        return $this->errorList;
    }

    private function getTotalAmount(array $orderList)
    {
        $amount = 0;
        $currencyCode = null;
        foreach ($orderList as $order) {
            if ($this->mollieHelper->useBaseCurrency($order->getStoreId())) {
                $amount += $order->getBaseGrandTotal();
                $currencyCode = $order->getBaseCurrencyCode();
                continue;
            }

            $amount += $order->getBaseGrandTotal();
            $currencyCode = $order->getBaseCurrencyCode();
        }

        return $this->mollieHelper->getAmountArray($currencyCode, $amount);
    }

    /**
     * @param array $orderList
     * @param $storeId
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @return array
     */
    private function buildPaymentData(array $orderList, $storeId): array
    {
        $firstOrder = reset($orderList);
        $paymentToken = $this->mollieHelper->getPaymentToken();
        $method = $this->mollieHelper->getMethodCode($firstOrder);
        $orderIds = array_map(function (OrderInterface $order) { return $order->getIncrementId(); }, $orderList);

        $paymentData = [
            'amount' => $this->getTotalAmount($orderList),
            'description' => $this->transactionDescription->forMultishippingTransaction($storeId),
            'billingAddress' => $this->molliePaymentsApi->getAddressLine($firstOrder->getBillingAddress()),
            'redirectUrl' => $this->transaction->getMultishippingRedirectUrl($orderList, $paymentToken),
            'webhookUrl' => $this->transaction->getWebhookUrl($storeId),
            'method' => $method,
            'metadata' => [
                'order_ids' => implode(', ', $orderIds),
                'store_id' => $storeId,
                'payment_token' => $paymentToken
            ],
            'locale' => $this->mollieHelper->getLocaleCode($storeId, Payments::CHECKOUT_TYPE),
        ];

        if ($method == 'banktransfer') {
            $paymentData['billingEmail'] = $firstOrder->getCustomerEmail();
            $paymentData['dueDate'] = $this->mollieHelper->getBanktransferDueDate($storeId);
        }

        if ($method == 'przelewy24') {
            $paymentData['billingEmail'] = $firstOrder->getCustomerEmail();
        }

        return $this->buildTransaction->execute($firstOrder, Payments::CHECKOUT_TYPE, $paymentData);
    }
}
