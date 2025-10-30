<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Controller\Adminhtml\Action;

use Exception;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\Session\Quote;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Redirect;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Mollie\Payment\Service\Order\Reorder;

class MarkAsPaid extends Action implements HttpPostActionInterface
{
    public function __construct(
        Context $context,
        private OrderRepositoryInterface $orderRepository,
        private Reorder $reorder,
        private Quote $session,
    ) {
        parent::__construct($context);
    }

    /**
     * This controller recreates the selected order with the checkmo payment method and marks it as completed. The
     * original order is then canceled.
     *
     * {@inheritDoc}
     */
    public function execute(): Redirect
    {
        $this->session->clearStorage();

        $orderId = $this->getRequest()->getParam('order_id');
        $originalOrder = $this->orderRepository->get($orderId);

        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            $order = $this->reorder->createAndInvoice(
                $originalOrder,
                Order::STATE_PROCESSING,
                Order::STATE_PROCESSING,
            );

            $this->messageManager->addSuccessMessage(
                __(
                    'We cancelled order %1, created this order and marked it as complete.',
                    $originalOrder->getIncrementId(),
                ),
            );

            return $resultRedirect->setPath('sales/order/view', ['order_id' => $order->getEntityId()]);
        } catch (Exception $exception) {
            $this->messageManager->addExceptionMessage($exception);

            return $resultRedirect->setPath('sales/order/view', ['order_id' => $originalOrder->getEntityId()]);
        }
    }
}
