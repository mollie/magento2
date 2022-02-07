<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Plugin\InstantPurchase\Button;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\InstantPurchase\Controller\Button\PlaceOrder;
use Mollie\Payment\Observer\CheckoutSubmitAllAfter\StartTransactionForInstantPurchaseOrders;

class PlaceOrderAddRedirectUrl
{
    /**
     * @var ResponseInterface
     */
    private $response;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var StartTransactionForInstantPurchaseOrders
     */
    private $startTransaction;

    public function __construct(
        ResponseInterface $response,
        SerializerInterface $serializer,
        ResultFactory $resultFactory,
        StartTransactionForInstantPurchaseOrders $startTransaction
    ) {
        $this->response = $response;
        $this->serializer = $serializer;
        $this->resultFactory = $resultFactory;
        $this->startTransaction = $startTransaction;
    }

    public function afterExecute(PlaceOrder $subject, Json $result)
    {
        $redirectUrl = $this->startTransaction->getRedirectUrl();
        if (!$redirectUrl) {
            return $result;
        }

        $result->renderResult($this->response);

        $body = $this->serializer->unserialize($this->response->getContent());

        $body['mollie_redirect_url'] = $redirectUrl;

        $result = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $result->setData($body);

        return $result;
    }
}
