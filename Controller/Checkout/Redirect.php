<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\Checkout;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\MethodInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Helper\General as MollieHelper;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Framework\View\Result\PageFactory;
use Mollie\Payment\Model\Methods\ApplePay;
use Mollie\Payment\Model\Methods\Creditcard;
use Mollie\Payment\Model\Methods\CreditcardVault;
use Mollie\Payment\Model\Methods\Directdebit;
use Mollie\Payment\Model\Mollie;

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
     * Redirect constructor.
     *
     * @param Context                           $context
     * @param Session                           $checkoutSession
     * @param PageFactory                       $resultPageFactory
     * @param PaymentHelper                     $paymentHelper
     * @param MollieHelper                      $mollieHelper
     * @param OrderManagementInterface          $orderManagement
     * @param Config                            $config
     * @param PaymentTokenRepositoryInterface   $paymentTokenRepository,
     * @param OrderRepositoryInterface          $orderRepository
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
        OrderRepositoryInterface $orderRepository
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->resultPageFactory = $resultPageFactory;
        $this->paymentHelper = $paymentHelper;
        $this->mollieHelper = $mollieHelper;
        $this->orderManagement = $orderManagement;
        $this->config = $config;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->orderRepository = $orderRepository;
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
                $redirectUrl = $this->startTransaction($methodInstance, $order);
                // This is deprecated since 2.18.0 and will be removed in a future version.
                if ($this->mollieHelper->useLoadingScreen($storeId)) {
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
            // @phpstan-ignore-next-line
            $this->formatExceptionMessage($exception, $methodInstance ?? null);
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
     * @param Exception $exception
     * @param MethodInterface|null $methodInstance
     */
    private function formatExceptionMessage(Exception $exception, MethodInterface $methodInstance = null)
    {
        if (stripos(
                $exception->getMessage(),
                'The webhook URL is invalid because it is unreachable from Mollie\'s point of view'
            ) !== false
        ) {
            $this->messageManager->addErrorMessage(
                __(
                    'The webhook URL is invalid because it is unreachable from Mollie\'s point of view. ' .
                    'View this article for more information: ' .
                    'https://github.com/mollie/magento2/wiki/Webhook-Communication-between-your-Magento-webshop-and-Mollie'
                )
            );

            return;
        }

        if ($methodInstance && stripos($exception->getMessage(), 'cURL error 28') !== false) {
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

    /**
     * @param Mollie $methodInstance
     * @param OrderInterface $order
     * @return mixed
     */
    private function startTransaction(Mollie $methodInstance, OrderInterface $order)
    {
        $redirectUrl = $methodInstance->startTransaction($order);

        /**
         * Directdebit does not return an url when in test mode.
         */
        if (!$redirectUrl && $methodInstance instanceof Directdebit && $this->config->isTestMode()) {
            $redirectUrl = $this->_url->getUrl('checkout/onepage/success/');
        }

        $emptyUrlAllowed = $methodInstance instanceof ApplePay || $methodInstance instanceof Creditcard;
        if (!$redirectUrl && $emptyUrlAllowed) {
            $redirectUrl = $this->_url->getUrl('checkout/onepage/success/');
        }

        return $redirectUrl;
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
