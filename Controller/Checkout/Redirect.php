<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\Checkout;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Helper\General as MollieHelper;
use Mollie\Payment\Model\Methods\ApplePay;
use Mollie\Payment\Model\Methods\CreditcardVault;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Service\Mollie\FormatExceptionMessages;
use Mollie\Payment\Service\Mollie\Order\RedirectUrl;

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
     * @var Config
     */
    private $config;
    /**
     * @var PaymentTokenRepositoryInterface
     */
    private $paymentTokenRepository;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var RedirectUrl
     */
    private $redirectUrl;
    /**
     * @var FormatExceptionMessages
     */
    private $formatExceptionMessages;

    /**
     * Redirect constructor.
     *
     * @param Context $context
     * @param Session $checkoutSession
     * @param PageFactory $resultPageFactory
     * @param PaymentHelper $paymentHelper
     * @param MollieHelper $mollieHelper
     * @param OrderManagementInterface $orderManagement
     * @param Config $config
     * @param PaymentTokenRepositoryInterface $paymentTokenRepository ,
     * @param OrderRepositoryInterface $orderRepository
     * @param RedirectUrl $redirectUrl
     * @param FormatExceptionMessages $formatExceptionMessages
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        PageFactory $resultPageFactory,
        PaymentHelper $paymentHelper,
        MollieHelper $mollieHelper,
        OrderManagementInterface $orderManagement,
        Config $config,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        OrderRepositoryInterface $orderRepository,
        RedirectUrl $redirectUrl,
        FormatExceptionMessages $formatExceptionMessages
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->resultPageFactory = $resultPageFactory;
        $this->paymentHelper = $paymentHelper;
        $this->mollieHelper = $mollieHelper;
        $this->orderManagement = $orderManagement;
        $this->config = $config;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->orderRepository = $orderRepository;
        $this->redirectUrl = $redirectUrl;
        $this->formatExceptionMessages = $formatExceptionMessages;
        parent::__construct($context);
    }

    /**
     * Execute Redirect to Mollie after placing order
     */
    public function execute()
    {
        try {
            $order = $this->getOrder();
        } catch (LocalizedException $exception) {
            $this->mollieHelper->addTolog('error', $exception->getMessage());
            return $this->_redirect('checkout/cart');
        }

        try {
            $payment = $order->getPayment();
            if (!isset($payment)) {
                return $this->_redirect('checkout/cart');
            }

            $method = $order->getPayment()->getMethod();
            $methodInstance = $this->getMethodInstance($method);
            if ($methodInstance instanceof Mollie) {
                $storeId = $order->getStoreId();
                $redirectUrl = $this->redirectUrl->execute($methodInstance, $order);
                // This is deprecated since 2.18.0 and will be removed in a future version.
                if (!($methodInstance instanceof ApplePay) &&
                    $this->mollieHelper->useLoadingScreen($storeId)
                ) {
                    $resultPage = $this->resultPageFactory->create();
                    $resultPage->getLayout()->initMessages();
                    $resultPage->getLayout()->getBlock('mollie_loading')->setMollieRedirect($redirectUrl);
                    return $resultPage;
                } else {
                    return $this->getResponse()->setRedirect($redirectUrl);
                }
            } else {
                $msg = __('Payment Method not found');
                $this->messageManager->addErrorMessage($msg);
                $this->mollieHelper->addTolog('error', $msg);
                $this->checkoutSession->restoreQuote();
                return $this->_redirect('checkout/cart');
            }
        } catch (Exception $exception) {
            $errorMessage = $this->formatExceptionMessages->execute($exception, $methodInstance ?? null);
            $this->messageManager->addErrorMessage($errorMessage);
            $this->mollieHelper->addTolog('error', $exception->getMessage());
            $this->checkoutSession->restoreQuote();
            $this->cancelUnprocessedOrder($order, $exception->getMessage());
            return $this->_redirect('checkout/cart');
        }
    }

    private function cancelUnprocessedOrder(OrderInterface $order, $message)
    {
        if (!$this->config->cancelFailedOrders()) {
            return;
        }

        try {
            $historyMessage = __('Canceled because an error occurred while redirecting the customer to Mollie');
            if ($message) {
                $historyMessage .= ':<br>' . PHP_EOL . $message;
            }

            $order->setState(Order::STATE_PENDING_PAYMENT);
            $this->orderManagement->cancel($order->getEntityId());
            $order->addCommentToStatusHistory($order->getEntityId(), $historyMessage);

            $this->mollieHelper->addToLog('info', sprintf('Canceled order %s', $order->getIncrementId()));
        } catch (Exception $e) {
            $message = sprintf('Cannot cancel order %s: %s', $order->getIncrementId(), $e->getMessage());
            $this->mollieHelper->addToLog('error', $message);
        }
    }

    /**
     * @return OrderInterface
     * @throws LocalizedException
     */
    private function getOrder()
    {
        $token = $this->getRequest()->getParam('paymentToken');

        if (!$token) {
            throw new LocalizedException(__('The required payment token is not available'));
        }

        $model = $this->paymentTokenRepository->getByToken($token);
        if (!$model) {
            throw new LocalizedException(__('The payment token %1 does not exists', $token));
        }

        return $this->orderRepository->get($model->getOrderId());
    }

    private function getMethodInstance(string $method): MethodInterface
    {
        $methodInstance = $this->paymentHelper->getMethodInstance($method);

        if ($methodInstance instanceof CreditcardVault) {
            return $this->paymentHelper->getMethodInstance('mollie_methods_creditcard');
        }

        return $methodInstance;
    }
}
