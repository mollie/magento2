<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Plugin\InstantPurchase\Button;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\InstantPurchase\Controller\Button\PlaceOrder;
use Mollie\Payment\Observer\CheckoutSubmitAllAfter\StartTransactionForInstantPurchaseOrders;

class PlaceOrderAddRedirectUrl
{
    public function __construct(
        private ResponseInterface $response,
        private SerializerInterface $serializer,
        private ResultFactory $resultFactory,
        private StartTransactionForInstantPurchaseOrders $startTransaction
    ) {}

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
