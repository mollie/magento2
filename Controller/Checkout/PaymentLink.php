<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Controller\Checkout;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Mollie\Payment\Model\Mollie;

class PaymentLink implements HttpGetActionInterface
{
    /**
     * @var RequestInterface
     */
    private $request;
    /**
     * @var EncryptorInterface
     */
    private $encryptor;
    /**
     * @var ResultFactory
     */
    private $resultFactory;
    /**
     * @var ManagerInterface
     */
    private $messageManager;
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var Mollie
     */
    private $mollie;

    public function __construct(
        RequestInterface $request,
        EncryptorInterface $encryptor,
        ResultFactory $resultFactory,
        ManagerInterface $messageManager,
        OrderRepositoryInterface $orderRepository,
        Mollie $mollie
    ) {
        $this->request = $request;
        $this->encryptor = $encryptor;
        $this->resultFactory = $resultFactory;
        $this->messageManager = $messageManager;
        $this->orderRepository = $orderRepository;
        $this->mollie = $mollie;
    }

    public function execute()
    {
        $orderKey = $this->request->getParam('order');
        if (!$orderKey) {
            return $this->returnStatusCode(400);
        }

        $id = $this->encryptor->decrypt(base64_decode($orderKey));

        if (empty($id)) {
            return $this->returnStatusCode(404);
        }

        try {
            $order = $this->orderRepository->get($id);
        } catch (NoSuchEntityException $exception) {
            return $this->returnStatusCode(404);
        }

        if (in_array($order->getState(), [Order::STATE_PROCESSING, Order::STATE_COMPLETE])) {
            $this->messageManager->addSuccessMessage(__('Your order has already been paid.'));

            return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setUrl('/');
        }

        $url = $this->mollie->startTransaction($order);

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setUrl($url);
    }

    public function returnStatusCode(int $code): ResultInterface
    {
        return $this->resultFactory->create(ResultFactory::TYPE_RAW)->setHttpResponseCode($code);
    }
}
