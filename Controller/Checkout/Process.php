<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\Checkout;

use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Model\Mollie as MollieModel;
use Mollie\Payment\Helper\General as MollieHelper;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;

/**
 * Class Process
 *
 * @package Mollie\Payment\Controller\Checkout
 */
class Process extends Action
{

    /**
     * @var Session
     */
    protected $checkoutSession;
    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;
    /**
     * @var MollieModel
     */
    protected $mollieModel;
    /**
     * @var MollieHelper
     */
    protected $mollieHelper;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * Process constructor.
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param PaymentHelper $paymentHelper
     * @param MollieModel $mollieModel
     * @param MollieHelper $mollieHelper
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        PaymentHelper $paymentHelper,
        MollieModel $mollieModel,
        MollieHelper $mollieHelper,
        OrderRepositoryInterface $orderRepository,
        ManagerInterface $eventManager
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->paymentHelper = $paymentHelper;
        $this->mollieModel = $mollieModel;
        $this->mollieHelper = $mollieHelper;
        $this->orderRepository = $orderRepository;
        $this->eventManager = $eventManager;
        parent::__construct($context);
    }

    /**
     * Return from mollie after payment
     */
    public function execute()
    {
        $orderIds = $this->getOrderIds();
        if (!$orderIds) {
            $this->mollieHelper->addTolog('error', __('Invalid return, missing order id.'));
            $this->messageManager->addNoticeMessage(__('Invalid return from Mollie.'));
            $this->_redirect('checkout/cart');
            return;
        }

        try {
            $result = [];
            $paymentToken = $this->getRequest()->getParam('payment_token');
            foreach ($orderIds as $orderId) {
                $result = $this->mollieModel->processTransaction($orderId, 'success', $paymentToken);
            }
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
            $this->messageManager->addExceptionMessage($e, __('There was an error checking the transaction status.'));
            $this->_redirect('checkout/cart');
            return;
        }

        if (!empty($result['success'])) {
            try {
                $this->checkoutSession->start();

                $this->_redirect('checkout/onepage/success?utm_nooverride=1');

                $this->eventManager->dispatch('mollie_checkout_success_redirect', [
                    'order_ids' => $orderIds,
                    'request' => $this->getRequest(),
                    'response' => $this->getResponse(),
                ]);
            } catch (\Exception $e) {
                $this->mollieHelper->addTolog('error', $e->getMessage());
                $this->messageManager->addErrorMessage(__('Something went wrong.'));
                $this->_redirect('checkout/cart');
            }

            return;
        }

        $this->handleNonSuccessResult($result, $orderIds);
    }

    /**
     * @return array
     */
    protected function getOrderIds(): array
    {
        if ($orderId = $this->getRequest()->getParam('order_id')) {
            return [$orderId];
        }

        return $this->getRequest()->getParam('order_ids') ?? [];
    }

    protected function handleNonSuccessResult(array $result, array $orderIds)
    {
        $this->checkIfLastRealOrder($orderIds);
        $this->checkoutSession->restoreQuote();
        $this->addResultMessage($result);

        $this->_redirect('checkout/cart');
    }

    /**
     * @param array $result
     */
    protected function addResultMessage(array $result)
    {
        if (!isset($result['status'])) {
            $this->messageManager->addErrorMessage(__('Something went wrong.'));
            return;
        }

        if ($result['status'] == 'canceled') {
            $this->messageManager->addNoticeMessage(__('Payment canceled, please try again.'));
            return;
        }

        if ($result['status'] == 'failed' && isset($result['method'])) {
            $this->messageManager->addErrorMessage(__('Payment of type %1 has been rejected. Decision is based on order and outcome of risk assessment.', $result['method']));
            return;
        }

        $this->messageManager->addErrorMessage(__('Something went wrong.'));
    }

    /**
     * @param array $orderIds
     */
    protected function checkIfLastRealOrder(array $orderIds)
    {
        if ($this->checkoutSession->getLastRealOrder()->getId()) {
            return;
        }

        try {
            $order = $this->orderRepository->get(end($orderIds));
            $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
        } catch (NoSuchEntityException $exception) {
            //
        }
    }
}
