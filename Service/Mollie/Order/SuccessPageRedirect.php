<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Order;

use Magento\Checkout\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Api\Data\TransactionToOrderInterface;
use Mollie\Payment\Api\TransactionToOrderRepositoryInterface;
use Mollie\Payment\Config;

class SuccessPageRedirect
{
    public function __construct(
        readonly private RequestInterface $request,
        readonly private ResponseInterface $response,
        readonly private Session $checkoutSession,
        readonly private ManagerInterface $eventManager,
        readonly private TransactionToOrderRepositoryInterface $transactionToOrderRepository,
        readonly private SearchCriteriaBuilder $searchCriteriaBuilder,
        readonly private Config $config,
        readonly private UrlInterface $urlBuilder,
    ) {}

    /**
     * This function has a few responsibilities:
     * - Check if the user has already been redirected to the success page.
     * - If not, mark as redirected and actually redirect.
     * - If already redirected, redirect to the cart page.
     */
    public function execute(OrderInterface $order, array $orderIds): ResponseInterface
    {
        $this->searchCriteriaBuilder->addFilter('transaction_id', $order->getMollieTransactionId());
        $this->searchCriteriaBuilder->addFilter('order_id', $order->getEntityId());
        $result = $this->transactionToOrderRepository->getList($this->searchCriteriaBuilder->create());

        // Fallback in case the transaction is not found.
        if ($result->getTotalCount() === 0) {
            $this->config->addToLog('warning', [
                'message' => 'Transaction not found in the transaction to order table. Redirecting to success page.',
                'order_id' => $order->getEntityId(),
            ]);

            return $this->redirectToSuccessPage($order, $orderIds);
        }

        $items = $result->getItems();
        /** @var TransactionToOrderInterface $item */
        $item = array_shift($items);

        if ($item->getRedirected() == 1) {
            // The user has already been redirected to the success page.
            $this->response->setRedirect($this->urlBuilder->getUrl('checkout/cart'));

            return $this->response;
        }

        $item->setRedirected(1);
        $this->transactionToOrderRepository->save($item);

        return $this->redirectToSuccessPage($order, $orderIds);
    }

    private function redirectToSuccessPage(OrderInterface $order, array $orderIds): ResponseInterface
    {
        $this->checkoutSession->setLastOrderId($order->getId());
        $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
        $this->checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
        $this->checkoutSession->setLastQuoteId($order->getQuoteId());

        $redirect = new DataObject([
            'path' => 'checkout/onepage/success',
            'query' => ['utm_nooverride' => 1],
        ]);

        $this->eventManager->dispatch('mollie_checkout_success_redirect', [
            'redirect' => $redirect,
            'order_ids' => $orderIds,
            'request' => $this->request,
            'response' => $this->response,
        ]);

        $this->response->setRedirect(
            $this->urlBuilder->getUrl(
                $redirect->getData('path'), [
                    '_query' => $redirect->getData('query'),
                    '_use_rewrite' => false,
                ]
            )
        );

        return $this->response;
    }
}
