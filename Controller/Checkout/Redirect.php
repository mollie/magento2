<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\Checkout;

use Exception;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Store\Model\ScopeInterface;
use Mollie\Payment\Helper\General as MollieHelper;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Result\PageFactory;

/**
 * Class Redirect
 *
 * @package Mollie\Payment\Controller\Checkout
 */
class Redirect extends Action
{

    /**
     * @var Session
     */
    protected $checkoutSession;
    /**
     * @var PageFactory
     */
    protected $resultPageFactory;
    /**
     * @var PaymentHelper
     */
    protected $paymentHelper;
    /**
     * @var MollieHelper
     */
    protected $mollieHelper;
    /**
     * @var OrderManagementInterface
     */
    private $orderManagement;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * Redirect constructor.
     *
     * @param Context                   $context
     * @param Session                   $checkoutSession
     * @param PageFactory               $resultPageFactory
     * @param PaymentHelper             $paymentHelper
     * @param MollieHelper              $mollieHelper
     * @param OrderManagementInterface  $orderManagement
     * @param ScopeConfigInterface      $scopeConfig
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        PageFactory $resultPageFactory,
        PaymentHelper $paymentHelper,
        MollieHelper $mollieHelper,
        OrderManagementInterface $orderManagement,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->resultPageFactory = $resultPageFactory;
        $this->paymentHelper = $paymentHelper;
        $this->mollieHelper = $mollieHelper;
        $this->orderManagement = $orderManagement;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    /**
     * Execute Redirect to Mollie after placing order
     */
    public function execute()
    {
        try {
            $order = $this->checkoutSession->getLastRealOrder();

            if (!$order) {
                $msg = __('Order not found.');
                $this->mollieHelper->addTolog('error', $msg);
                $this->_redirect('checkout/cart');
                return;
            }

            $payment = $order->getPayment();
            if (!isset($payment)) {
                $this->_redirect('checkout/cart');
                return;
            }

            $method = $order->getPayment()->getMethod();
            $methodInstance = $this->paymentHelper->getMethodInstance($method);
            if ($methodInstance instanceof \Mollie\Payment\Model\Mollie) {
                $storeId = $order->getStoreId();
                $redirectUrl = $methodInstance->startTransaction($order);
                if ($this->mollieHelper->useLoadingScreen($storeId)) {
                    $resultPage = $this->resultPageFactory->create();
                    $resultPage->getLayout()->initMessages();
                    $resultPage->getLayout()->getBlock('mollie_loading')->setMollieRedirect($redirectUrl);
                    return $resultPage;
                } else {
                    $this->getResponse()->setRedirect($redirectUrl);
                }
            } else {
                $msg = __('Payment Method not found');
                $this->messageManager->addErrorMessage($msg);
                $this->mollieHelper->addTolog('error', $msg);
                $this->checkoutSession->restoreQuote();
                $this->_redirect('checkout/cart');
            }
        } catch (Exception $exception) {
            $this->formatExceptionMessage($exception, $methodInstance);
            $this->mollieHelper->addTolog('error', $exception->getMessage());
            $this->checkoutSession->restoreQuote();
            $this->cancelUnprocessedOrder($order, $exception->getMessage());
            $this->_redirect('checkout/cart');
        }
    }

    private function cancelUnprocessedOrder(OrderInterface $order, $message)
    {
        if (!empty($order->getMollieTransactionId())) {
            return;
        }

        if (!$this->scopeConfig->isSetFlag(
            'payment/mollie_general/cancel_failed_orders',
            ScopeInterface::SCOPE_STORE
        )) {
            return;
        }

        try {
            $historyMessage = __('Canceled because an error occurred while redirecting the customer to Mollie');
            if ($message) {
                $historyMessage .= ':<br>' . PHP_EOL . $message;
            }

            $this->orderManagement->cancel($order->getEntityId());
            $order->addCommentToStatusHistory($order->getEntityId(), $historyMessage);

            $this->mollieHelper->addToLog('info', sprintf('Canceled order %s', $order->getIncrementId()));
        } catch (Exception $e) {
            $message = sprintf('Cannot cancel order %s: %s', $order->getIncrementId(), $e->getMessage());
            $this->mollieHelper->addToLog('error', $message);
        }
    }

    /**
     * @param Exception $exception
     * @param MethodInterface $methodInstance
     */
    private function formatExceptionMessage(Exception $exception, MethodInterface $methodInstance)
    {
        if (stripos($exception->getMessage(), 'cURL error 28') !== false) {
            $this->messageManager->addErrorMessage(
                __(
                    'A Timeout while connecting to %1 occurred, this could be the result of an outage. ' .
                    'Please try again or select another payment method.',
                    $methodInstance->getTitle()
                )
            );

            return;
        }

        $this->messageManager->addExceptionMessage($exception, __($exception->getMessage()));
    }
}
