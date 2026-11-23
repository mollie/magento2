<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Controller\Express;

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
use Mollie\Api\Resources\Payment;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Mollie;
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Payment\Service\LockService;
use Mollie\Payment\Service\Mollie\Express\ProductPaymentMetadata;
use Mollie\Payment\Service\Mollie\Express\ProductPaymentToken;
use Mollie\Payment\Service\Mollie\Express\ProductQuote;
use Mollie\Payment\Service\Mollie\Order\ConvertComponentsPaymentToOrder;
use Mollie\Payment\Service\Mollie\Order\GetOrderByTransactionId;
use Mollie\Payment\Service\Mollie\Wrapper\GetExpressPayment;
use stdClass;
use Throwable;

class Webhook implements HttpPostActionInterface, HttpGetActionInterface, CsrfAwareActionInterface
{
    private const PLACEMENT_PRODUCT = 'product';

    private bool $created = false;

    public function __construct(
        readonly private RequestInterface $request,
        readonly private Raw $response,
        readonly private CartRepositoryInterface $cartRepository,
        readonly private Config $config,
        readonly private GetExpressPayment $getExpressPayment,
        readonly private Mollie $mollieModel,
        readonly private LockService $lockService,
        readonly private ConvertComponentsPaymentToOrder $convertComponentPaymentToOrder,
        readonly private GetOrderByTransactionId $getOrderByTransactionId,
        readonly private ProductQuote $productQuote,
        readonly private ProductPaymentToken $productPaymentToken,
        readonly private StoreManagerInterface $storeManager,
    ) {}

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function execute(): ResultInterface
    {
        $quoteId = (string)$this->request->getParam('quoteId');
        $paymentId = (string)$this->request->getParam('id');

        if ($paymentId === '' || ($quoteId === '' && !$this->isProductPlacement())) {
            throw new NotFoundException(__('Invalid payment id or quote id.'));
        }

        $lockName = 'mollie_express_webhook_' . ($quoteId !== '' ? $quoteId : $paymentId);
        if (!$this->lockService->lock($lockName)) {
            throw new LocalizedException(__('Could not acquire lock for quote ID: %1', $quoteId));
        }

        try {
            $payment = $this->getExpressPayment->execute($this->getStoreId($quoteId), $paymentId);

            if (in_array($payment->status, ['expired', 'failed'])) {
                $this->response->setContents('ok');
                $this->response->setHttpResponseCode(200);

                return $this->response;
            }

            $this->config->addToLog('info', [
                'message' => 'Received express webhook',
                'payment' => $payment,
            ]);

            $cart = $this->getBaseCart($payment, $quoteId);

            $order = $this->placeOrRetrieveOrder($cart, $payment);
            $this->linkProductPaymentToken($payment, $order);
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

    private function isProductPlacement(): bool
    {
        return $this->request->getParam('placement') === self::PLACEMENT_PRODUCT;
    }

    private function getStoreId(string $quoteId): int
    {
        if ($quoteId !== '') {
            return (int)$this->cartRepository->get((int)$quoteId)->getStoreId();
        }

        $storeId = (int)$this->request->getParam('storeId');
        if ($storeId !== 0) {
            return $storeId;
        }

        return (int)$this->storeManager->getStore()->getId();
    }

    /**
     * A product page session has no saved quote, so it is built again from the payment metadata.
     */
    private function getBaseCart(Payment $payment, string $quoteId): CartInterface
    {
        if ($quoteId === '') {
            $metadata = ProductPaymentMetadata::fromMollie($payment->metadata);

            return $this->productQuote->create($metadata->buyRequest, $metadata->storeId, $metadata->customerId);
        }

        $this->validateThatWeHaveTheCorrectQuote($payment, $quoteId);

        return $this->cartRepository->get((int)$quoteId);
    }

    private function linkProductPaymentToken(Payment $payment, OrderInterface $order): void
    {
        if (!ProductPaymentMetadata::isProductPayment($payment->metadata)) {
            return;
        }

        $this->productPaymentToken->link($order, ProductPaymentMetadata::fromMollie($payment->metadata)->paymentToken);
    }

    public function placeOrRetrieveOrder(CartInterface $cart, Payment $payment): OrderInterface
    {
        $order = $this->getOrderByTransactionId->execute($payment->id);
        if ($order !== null) {
            return $order;
        }

        $this->created = true;
        return $this->convertComponentPaymentToOrder->execute($cart, $payment);
    }

    public function updateMetadata(Payment $payment, OrderInterface $order): void
    {
        $metadata = $payment->metadata instanceof stdClass ? $payment->metadata : new stdClass();
        if (property_exists($metadata, 'order_id')) {
            return;
        }

        $metadata->order_id = $order->getEntityId();
        $payment->metadata = $metadata;
        $payment->update();
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * The parameter is a string on purpose: the code generator of Magento 2.4.5 cannot build an interceptor
     * for a public method with a mixed parameter.
     */
    public function validateThatWeHaveTheCorrectQuote(Payment $payment, string $quoteId): void
    {
        $metadataQuoteId = $payment->metadata instanceof stdClass ? ($payment->metadata->quoteId ?? null) : null;
        if ($metadataQuoteId === $quoteId) {
            return;
        }

        throw new LocalizedException(
            __(
                'Quote ID does not match the payment metadata. URL: %1, Metadata: %2',
                $quoteId,
                $metadataQuoteId
            )
        );
    }
}
