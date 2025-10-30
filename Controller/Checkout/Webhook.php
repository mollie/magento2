<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Controller\Checkout;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Helper\General as MollieHelper;
use Mollie\Payment\Model\Mollie as MollieModel;
use Mollie\Payment\Service\Mollie\ProcessTransaction;
use Mollie\Payment\Service\OrderLockService;

class Webhook extends Action implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var ResultFactory
     */
    protected $resultFactory;

    public function __construct(
        Context $context,
        protected Session $checkoutSession,
        protected MollieModel $mollieModel,
        protected MollieHelper $mollieHelper,
        private OrderRepositoryInterface $orderRepository,
        private EncryptorInterface $encryptor,
        private OrderLockService $orderLockService,
        private ProcessTransaction $processTransaction,
    ) {
        $this->resultFactory = $context->getResultFactory();
        parent::__construct($context);
    }

    /**
     * Mollie webhook
     */
    public function execute()
    {
        if ($this->getRequest()->getParam('testByMollie')) {
            return $this->getOkResponse();
        }

        $transactionId = $this->getRequest()->getParam('id');
        if (!$transactionId) {
            return $this->getOkResponse();
        }

        try {
            $orders = $this->getOrders();
            if (empty($orders)) {
                return $this->getErrorResponse(200, 'No orders found');
            }

            $this->processOrders($orders);

            return $this->getOkResponse();
        } catch (Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());

            return $this->getErrorResponse($e->getCode() ?: 503);
        }
    }

    public function processOrders(array $orders): void
    {
        $transactionId = $this->getRequest()->getParam('id');

        foreach ($orders as $order) {
            // If this returns true, it means that the order is just created but did go straight to "paid".
            // That can happen for Apple Pay and Credit Card. In that case, Mollie immediately sends a webhook,
            // but we are not ready to process it yet.
            if ($this->orderLockService->isLocked($order)) {
                // @phpcs:ignore Magento2.Exceptions.DirectThrow.FoundDirectThrow
                throw new Exception('Order is locked, skipping webhook', 425);
            }

            $this->processTransaction->execute((int) $order->getEntityId(), $transactionId);
        }
    }

    private function getOkResponse()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $result->setHeader('content-type', 'text/plain');
        $result->setContents('OK');

        return $result;
    }

    private function getErrorResponse(int $code, ?string $message = null): Json
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $result->setData(['error' => true]);

        if ($message) {
            $result->setData(['message' => $message]);
        }

        $result->setHttpResponseCode($code);

        return $result;
    }

    /**
     * @return OrderInterface[]
     */
    private function getOrders(): array
    {
        $orders = [];
        $transactionId = $this->getRequest()->getParam('id');
        $orderIds = $this->mollieModel->getOrderIdsByTransactionId($transactionId);

        foreach ($orderIds as $id) {
            $orders[] = $this->orderRepository->get($id);
        }

        if ($orders) {
            return $orders;
        }

        // Fallback to order ids from the URL.
        $orderIds = $this->getRequest()->getParam('orderId', []);
        foreach ($orderIds as $id) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction.Discouraged
            $id = $this->encryptor->decrypt(base64_decode($id));
            $orders[] = $this->orderRepository->get($id);
        }

        return $orders;
    }
}
