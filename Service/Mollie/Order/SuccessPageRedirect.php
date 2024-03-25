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
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Api\Data\TransactionToOrderInterface;
use Mollie\Payment\Api\TransactionToOrderRepositoryInterface;
use Mollie\Payment\Config;

class SuccessPageRedirect
{
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var ResponseInterface
     */
    private $response;
    /**
     * @var Session
     */
    private $checkoutSession;
    /**
     * @var ManagerInterface
     */
    private $eventManager;
    /**
     * @var RedirectInterface
     */
    private $redirect;
    /**
     * @var TransactionToOrderRepositoryInterface
     */
    private $transactionToOrderRepository;
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var Config
     */
    private $config;

    public function __construct(
        RedirectInterface $redirect,
        RequestInterface $request,
        ResponseInterface $response,
        Session $checkoutSession,
        ManagerInterface $eventManager,
        TransactionToOrderRepositoryInterface $transactionToOrderRepository,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        Config $config
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->checkoutSession = $checkoutSession;
        $this->eventManager = $eventManager;
        $this->redirect = $redirect;
        $this->transactionToOrderRepository = $transactionToOrderRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->config = $config;
    }

    /**
     * This function has a few responsibilities:
     * - Check if the user has already been redirected to the success page.
     * - If not, mark as redirected and actually redirect.
     * - If already redirected, redirect to the cart page.
     */
    public function execute(OrderInterface $order, array $orderIds): void
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
            $this->redirectToSuccessPage($order, $orderIds);
            return;
        }

        $items = $result->getItems();
        /** @var TransactionToOrderInterface $item */
        $item = array_shift($items);

        if ($item->getRedirected() == 1) {
            // The user has already been redirected to the success page.
            $this->redirect->redirect($this->response, 'checkout/cart');
            return;
        }

        $item->setRedirected(1);
        $this->transactionToOrderRepository->save($item);

        $this->redirectToSuccessPage($order, $orderIds);
    }

    /**
     * @param OrderInterface $order
     * @param array $orderIds
     * @return void
     */
    private function redirectToSuccessPage(OrderInterface $order, array $orderIds): void
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

        $this->redirect->redirect(
            $this->response,
            $redirect->getData('path'),
            [
                '_query' => $redirect->getData('query'),
                '_use_rewrite' => false,
            ]
        );
    }
}
