<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Webapi;

use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Sales\Model\Order;
use Mollie\Payment\Api\Webapi\GetPaymentLinkRedirectInterface;
use Mollie\Payment\Service\Mollie\StartTransaction;
use Mollie\Payment\Webapi\GetPaymentLinkRedirect;

class GetPaymentLinkRedirectTestWebApi extends AbstractTestWebApi
{
    /**
     * @var string
     */
    protected $class = GetPaymentLinkRedirectInterface::class;

    /**
     * @var string[]
     */
    protected $methods = ['byHash'];

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testRetrievesTheOrder(): void
    {
        $order = $this->loadOrder('100000001');
        $order->setState(Order::STATE_PENDING_PAYMENT);
        $order->save();

        $startTransactionMock = $this->createMock(StartTransaction::class);
        $startTransactionMock->method('execute')->willReturn('https://www.mollie.com');
        $this->objectManager->addSharedInstance($startTransactionMock, StartTransaction::class);

        $encryptor = $this->objectManager->get(EncryptorInterface::class);

        $hash = base64_encode($encryptor->encrypt((string) $order->getEntityId()));

        $instance = $this->objectManager->create(GetPaymentLinkRedirect::class);
        $result = $instance->byHash($hash);

        $this->assertFalse($result->isAlreadyPaid());
        $this->assertEquals('https://www.mollie.com', $result->getRedirectUrl());
    }

    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @return void
     */
    public function testDoesNotIncludeLinkWhenAlreadyPaid(): void
    {
        $order = $this->loadOrder('100000001');
        $order->setState(Order::STATE_PROCESSING);
        $order->save();

        $encryptor = $this->objectManager->get(EncryptorInterface::class);
        $hash = base64_encode($encryptor->encrypt((string) $order->getEntityId()));

        $instance = $this->objectManager->create(GetPaymentLinkRedirect::class);
        $result = $instance->byHash($hash);

        $this->assertTrue($result->isAlreadyPaid());
        $this->assertEquals('', $result->getRedirectUrl());
    }
}
