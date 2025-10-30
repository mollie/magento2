<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie\Order;

use Laminas\Http\Headers;
use Magento\Framework\App\ResponseInterface;
use Mollie\Payment\Api\Data\TransactionToOrderInterface;
use Mollie\Payment\Api\TransactionToOrderRepositoryInterface;
use Mollie\Payment\Service\Mollie\Order\SuccessPageRedirect;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class SuccessPageRedirectTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testRedirectsToSuccessPage(): void
    {
        $transactionId = 'tr_abc123';
        $order = $this->loadOrder('100000001');
        $order->setMollieTransactionId($transactionId);

        $transactionToOrder = $this->objectManager->create(TransactionToOrderInterface::class);
        $transactionToOrder->setOrderId((int) $order->getEntityId());
        $transactionToOrder->setTransactionId($transactionId);
        $transactionToOrder->setRedirected(0); // This is the default but being explicit
        $this->objectManager->get(TransactionToOrderRepositoryInterface::class)->save($transactionToOrder);

        $instance = $this->objectManager->create(SuccessPageRedirect::class);
        $instance->execute($order, [$order->getEntityId()]);

        /** @var ResponseInterface $response */
        $response = $this->objectManager->get(ResponseInterface::class);
        /** @var Headers $headers */
        $headers = $response->getHeaders();

        $this->assertStringContainsString(
            'checkout/onepage/success',
            $headers->get('Location')->getFieldValue(),
        );
    }
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testRedirectsToCartWhenAlreadyRedirected(): void
    {
        $transactionId = 'tr_abc123';
        $order = $this->loadOrder('100000001');
        $order->setMollieTransactionId($transactionId);

        $transactionToOrder = $this->objectManager->create(TransactionToOrderInterface::class);
        $transactionToOrder->setOrderId((int) $order->getEntityId());
        $transactionToOrder->setTransactionId($transactionId);

        // Mark it as already redirected
        $transactionToOrder->setRedirected(1);

        $this->objectManager->get(TransactionToOrderRepositoryInterface::class)->save($transactionToOrder);

        $instance = $this->objectManager->create(SuccessPageRedirect::class);
        $instance->execute($order, [$order->getEntityId()]);

        /** @var ResponseInterface $response */
        $response = $this->objectManager->get(ResponseInterface::class);
        /** @var Headers $headers */
        $headers = $response->getHeaders();

        $this->assertStringContainsString(
            'checkout/cart',
            $headers->get('Location')->getFieldValue(),
        );
    }
}
