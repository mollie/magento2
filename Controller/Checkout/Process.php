<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\Checkout;

use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Model\Mollie as MollieModel;
use Mollie\Payment\Helper\General as MollieHelper;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Mollie\Payment\Multishipping\CheckoutRedirect;

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
     * @var CheckoutRedirect
     */
    private $multishippingRedirect;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * Process constructor.
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param PaymentHelper $paymentHelper
     * @param MollieModel $mollieModel
     * @param MollieHelper $mollieHelper
     * @param CheckoutRedirect $checkoutRedirect
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        PaymentHelper $paymentHelper,
        MollieModel $mollieModel,
        MollieHelper $mollieHelper,
        CheckoutRedirect $checkoutRedirect,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->paymentHelper = $paymentHelper;
        $this->mollieModel = $mollieModel;
        $this->mollieHelper = $mollieHelper;
        $this->multishippingRedirect = $checkoutRedirect;
        $this->orderRepository = $orderRepository;
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
                if (count($orderIds) > 1) {
                    $this->multishippingRedirect->redirect();
                } else {
                    $this->_redirect('checkout/onepage/success?utm_nooverride=1');
                }
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
        if (!$this->checkoutSession->getLastRealOrder()->getId()) {
            $order = $this->orderRepository->get(end($orderIds));
            $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
        }

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
}
