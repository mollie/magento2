<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Controller\Express;

use Magento\Framework\Api\SearchCriteriaBuilderFactory;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Service\LockService;
use Mollie\Payment\Service\Mollie\Order\ConvertComponentsPaymentToOrder;
use Mollie\Payment\Service\Mollie\Wrapper\GetExpressPayment;
use Throwable;

class Webhook implements HttpPostActionInterface, HttpGetActionInterface, CsrfAwareActionInterface
{
    private bool $created = false;

    public function __construct(
        readonly private RequestInterface $request,
        readonly private Raw $response,
        readonly private SearchCriteriaBuilderFactory $searchCriteriaBuilderFactory,
        readonly private CartRepositoryInterface $cartRepository,
        readonly private OrderRepositoryInterface $orderRepository,
        readonly private Config $config,
        readonly private GetExpressPayment $getExpressPayment,
        readonly private Mollie $mollieModel,
        readonly private LockService $lockService,
        readonly private ConvertComponentsPaymentToOrder $convertComponentPaymentToOrder,
    ) {}

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function execute(): ResultInterface
    {
        $quoteId = $this->request->getParam('quoteId');
        $paymentId = $this->request->getParam('id');

        if (!$quoteId || !$paymentId) {
            throw new NotFoundException(__('Invalid payment id or quote id.'));
        }

        $lockName = 'mollie_express_webhook_' . $quoteId;
        if (!$this->lockService->lock($lockName)) {
            throw new LocalizedException(__('Could not acquire lock for quote ID: %1', $quoteId));
        }

        try {
            $cart = $this->cartRepository->get($quoteId);

            $payment = $this->getExpressPayment->execute((int)$cart->getStoreId(), $paymentId);

            if (in_array($payment->status, ['expired', 'failed'])) {
                $this->response->setContents('ok');
                $this->response->setHttpResponseCode(200);

                return $this->response;
            }

            $this->config->addToLog('info', [
                'message' => 'Received express webhook',
                'payment' => $payment,
            ]);

            $this->validateThatWeHaveTheCorrectQuote($payment, $quoteId);

            $order = $this->placeOrRetrieveOrder($cart, $payment);
            $this->updateMetadata($payment, $order);

            $this->mollieModel->processTransactionForOrder($order);
        } catch (Throwable $exception) {
            $this->config->addToLog('Error while processing express webhook', [
                'message' => $exception->getMessage(),
                'code' => $exception->getCode(),
                'trace' => $exception->getTraceAsString(),
            ]);

            throw $exception;
        } finally {
            $this->lockService->unlock($lockName);
        }

        $this->response->setContents('ok');
        $this->response->setHttpResponseCode($this->created ? 201 : 200);

        return $this->response;
    }

    public function placeOrRetrieveOrder(CartInterface $cart, Payment $payment): OrderInterface
    {
        $searchCriteriaBuilder = $this->searchCriteriaBuilderFactory->create();
        $searchCriteriaBuilder->addFilter('quote_id', $cart->getEntityId());
        $searchCriteriaBuilder->addFilter('mollie_transaction_id', $payment->id);

        $items = $this->orderRepository->getList($searchCriteriaBuilder->create())->getItems();
        if ($items !== []) {
            return array_shift($items);
        }

        $this->created = true;
        return $this->convertComponentPaymentToOrder->execute($cart, $payment);
    }

    public function updateMetadata(Payment $payment, OrderInterface $order): void
    {
        if (property_exists($payment->metadata, 'order_id')) {
            return;
        }

        $payment->metadata->order_id = $order->getEntityId();
        $payment->update();
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    public function validateThatWeHaveTheCorrectQuote(Payment $payment, mixed $quoteId): void
    {
        if ($payment->metadata->quoteId === $quoteId) {
            return;
        }

        throw new LocalizedException(
            __(
                'Quote ID does not match the payment metadata. URL: %1, Metadata: %2',
                $quoteId,
                $payment->metadata->quoteId
            )
        );
    }
}
