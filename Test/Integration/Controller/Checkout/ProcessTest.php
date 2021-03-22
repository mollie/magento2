<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Controller\Checkout;

use Magento\Framework\Message\MessageInterface;
use Magento\TestFramework\TestCase\AbstractController;
use Mollie\Payment\Model\Mollie;

class ProcessTest extends AbstractController
{
    public function testDoesRedirectsToCartWhenNoIdProvided()
    {
        $this->dispatch('mollie/checkout/process');

        $this->assertRedirect($this->stringContains('checkout/cart'));
        $this->assertSessionMessages($this->equalTo(['Invalid return from Mollie.']), MessageInterface::TYPE_NOTICE);
    }

    public function testRedirectsToCartOnException()
    {
        $mollieModel = $this->createMock(Mollie::class);
        $mollieModel->method('processTransaction')->willThrowException(new \Exception('[TEST] Something went wrong'));

        $this->_objectManager->addSharedInstance($mollieModel, Mollie::class);

        $this->dispatch('mollie/checkout/process?order_id=123');

        $this->assertRedirect($this->stringContains('checkout/cart'));
        $this->assertSessionMessages(
            $this->equalTo(['There was an error checking the transaction status.']),
            MessageInterface::TYPE_ERROR
        );
    }

    public function testUsesOrderIdParameter()
    {
        $mollieModel = $this->createMock(Mollie::class);
        $mollieModel->expects($this->once())->method('processTransaction')->with('123');

        $this->_objectManager->addSharedInstance($mollieModel, Mollie::class);

        $this->dispatch('mollie/checkout/process?order_id=123');
    }

    public function testUsesOrderIdsParameter()
    {
        $mollieModel = $this->createMock(Mollie::class);
        $mollieModel->expects($this->exactly(2))->method('processTransaction')->willReturnOnConsecutiveCalls('123', '456');

        $this->_objectManager->addSharedInstance($mollieModel, Mollie::class);

        $this->dispatch('mollie/checkout/process?order_ids[]=123&order_ids[]=456');
    }

    public function testRedirectsToSuccessPage()
    {
        $mollieModel = $this->createMock(Mollie::class);
        $mollieModel->method('processTransaction')->willReturn(['success' => true]);

        $this->_objectManager->addSharedInstance($mollieModel, Mollie::class);

        $this->dispatch('mollie/checkout/process?order_ids[]=123&order_ids[]=456');

        $this->assertRedirect($this->stringContains('multishipping/checkout/success?utm_nooverride=1'));
    }
}
