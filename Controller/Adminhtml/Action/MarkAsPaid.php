<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\Adminhtml\Action;

use Magento\Backend\App\Action;
use Magento\Backend\Model\Session\Quote;
use Magento\Framework\DB\TransactionFactory;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\AdminOrder\Create;
use Magento\Sales\Model\Order;

class MarkAsPaid extends Action
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var Create
     */
    private $orderCreate;

    /**
     * @var Quote
     */
    private $quoteSession;

    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    public function __construct(
        Action\Context $context,
        OrderRepositoryInterface $orderRepository,
        Create $orderCreate,
        Quote $quoteSession,
        TransactionFactory $transactionFactory
    ) {
        parent::__construct($context);

        $this->orderRepository = $orderRepository;
        $this->orderCreate = $orderCreate;
        $this->quoteSession = $quoteSession;
        $this->transactionFactory = $transactionFactory;
    }

    /**
     * This controller recreates the selected order with the checkmo payment method and marks it as completed. The
     * original order is then canceled.
     *
     * {@inheritDoc}
     */
    public function execute()
    {
        $orderId = $this->getRequest()->getParam('order_id');

        $originalOrder = $this->orderRepository->get($orderId);
        $originalOrder->setReordered(true);

        $resultRedirect = $this->resultRedirectFactory->create();
        try {
            $order = $this->recreateOrder($originalOrder);
            $this->cancelOriginalOrder($originalOrder, $order->getIncrementId());
            $this->save($order, $originalOrder);

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

    /**
     * @param OrderInterface $originalOrder
     * @return OrderInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function recreateOrder(OrderInterface $originalOrder)
    {
        $this->quoteSession->setOrderId($originalOrder->getEntityId());
        $this->quoteSession->setUseOldShippingMethod(true);
        $this->orderCreate->initFromOrder($originalOrder);
        $this->orderCreate->setPaymentMethod('checkmo');

        $order = $this->orderCreate->createOrder();
        $order->setState(Order::STATE_COMPLETE);
        $order->setStatus(Order::STATE_COMPLETE);

        return $order;
    }

    /**
     * @param OrderInterface $originalOrder
     * @param string $newIncrementId
     */
    private function cancelOriginalOrder(OrderInterface $originalOrder, $newIncrementId)
    {
        $comment = __('We created a new order with increment ID: %1', $newIncrementId);
        $originalOrder->addCommentToStatusHistory($comment);
        $originalOrder->cancel();
    }

    /**
     * @param OrderInterface $order
     * @param OrderInterface $originalOrder
     * @throws \Exception
     */
    private function save(OrderInterface $order, OrderInterface $originalOrder)
    {
        $transaction = $this->transactionFactory->create();
        $transaction->addObject($order);
        $transaction->addObject($originalOrder);
        $transaction->save();
    }
}
