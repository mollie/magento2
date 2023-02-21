<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Controller\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Mollie\Payment\Helper\General as MollieHelper;
use Mollie\Payment\Model\Mollie as MollieModel;

/**
 * Class Webhook
 *
 * @package Mollie\Payment\Controller\Checkout
 */
class Webhook extends Action
{
    /**
     * @var Session
     */
    protected $checkoutSession;
    /**
     * @var ResultFactory
     */
    protected $resultFactory;
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
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * Webhook constructor.
     *
     * @param Context       $context
     * @param Session       $checkoutSession
     * @param MollieModel   $mollieModel
     * @param MollieHelper  $mollieHelper
     * @param OrderRepositoryInterface $orderRepository
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        Context $context,
        Session $checkoutSession,
        MollieModel $mollieModel,
        MollieHelper $mollieHelper,
        OrderRepositoryInterface $orderRepository,
        EncryptorInterface $encryptor
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->resultFactory = $context->getResultFactory();
        $this->mollieModel = $mollieModel;
        $this->mollieHelper = $mollieHelper;
        $this->orderRepository = $orderRepository;
        $this->encryptor = $encryptor;
        parent::__construct($context);
    }

    /**
     * Mollie webhook
     */
    public function execute()
    {
        if ($this->getRequest()->getParam('testByMollie')) {
            return $this->getOkResponse();
        }

        $transactionId = $this->getRequest()->getParam('id');
        if (!$transactionId) {
            return $this->getOkResponse();
        }

        try {
            $orders = $this->getOrders();
            if (empty($orders)) {
                return $this->getErrorResponse(200, 'No orders found');
            }

            foreach ($orders as $order) {
                $order->setMollieTransactionId($transactionId);
                $this->mollieModel->processTransactionForOrder($order, 'webhook');
            }

            return $this->getOkResponse();
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());

            return $this->getErrorResponse(503);
        }
    }

    private function getOkResponse()
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        $result->setHeader('content-type', 'text/plain');
        $result->setContents('OK');
        return $result;
    }

    private function getErrorResponse(int $code, string $message = null): Json
    {
        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $result->setData(['error' => true]);

        if ($message) {
            $result->setData(['message' => $message]);
        }

        $result->setHttpResponseCode($code);

        return $result;
    }

    /**
     * @return OrderInterface[]
     */
    private function getOrders(): array
    {
        $orders = [];
        $transactionId = $this->getRequest()->getParam('id');
        $orderIds = $this->mollieModel->getOrderIdsByTransactionId($transactionId);

        foreach ($orderIds as $id) {
            $orders[] = $this->orderRepository->get($id);
        }

        if ($orders) {
            return $orders;
        }

        // Fallback to order ids from the URL.
        $orderIds = $this->getRequest()->getParam('orderId', []);
        foreach ($orderIds as $id) {
            $id = $this->encryptor->decrypt(base64_decode($id));
            $orders[] = $this->orderRepository->get($id);
        }

        return $orders;
    }
}
