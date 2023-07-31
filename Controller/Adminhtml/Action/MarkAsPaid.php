<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\Adminhtml\Action;

use Magento\Backend\App\Action;
use Magento\Backend\Model\Session\Quote;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Mollie\Payment\Service\Order\Reorder;

class MarkAsPaid extends Action
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var Reorder
     */
    private $reorder;

    /**
     * @var Quote
     */
    private $session;

    public function __construct(
        Action\Context $context,
        OrderRepositoryInterface $orderRepository,
        Reorder $reorder,
        Quote $session
    ) {
        parent::__construct($context);

        $this->orderRepository = $orderRepository;
        $this->reorder = $reorder;
        $this->session = $session;
    }

    /**
     * This controller recreates the selected order with the checkmo payment method and marks it as completed. The
     * original order is then canceled.
     *
     * {@inheritDoc}
     */
    public function execute()
    {
        $this->session->clearStorage();

        $orderId = $this->getRequest()->getParam('order_id');
        $originalOrder = $this->orderRepository->get($orderId);

        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            $order = $this->reorder->createAndInvoice(
                $originalOrder,
                Order::STATE_PROCESSING,
                Order::STATE_PROCESSING
            );

            $this->messageManager->addSuccessMessage(
                __(
                    'We cancelled order %1, created this order and marked it as complete.',
                    $originalOrder->getIncrementId()
                )
            );

            return $resultRedirect->setPath('sales/order/view', ['order_id' => $order->getEntityId()]);
        } catch (\Exception $exception) {
            $this->messageManager->addExceptionMessage($exception);

            return $resultRedirect->setPath('sales/order/view', ['order_id' => $originalOrder->getEntityId()]);
        }
    }
}
