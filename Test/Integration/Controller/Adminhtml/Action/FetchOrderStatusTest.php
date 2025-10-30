<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Controller\Adminhtml\Action;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\MessageInterface;
use Magento\TestFramework\TestCase\AbstractBackendController;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Model\Mollie as MollieHelper;
use function json_decode;

class FetchOrderStatusTest extends AbstractBackendController
{
    public function testProcessesTheTransaction(): void
    {
        $mollieHelperMock = $this->createMock(MollieHelper::class);
        $mollieHelperMock->method('orderHasUpdate')->willReturn(true);
        $mollieHelperMock->expects($this->once())
            ->method('processTransaction')
            ->with('999', 'webhook')
            ->willReturn($this->_objectManager->create(ProcessTransactionResponse::class, [
                'success' => true,
                'status' => '[TEST] Test successful',
                'order_id' => -999,
                'type' => 'webhook',
            ]));

        $this->_objectManager->addSharedInstance($mollieHelperMock, MollieHelper::class);

        $this->getRequest()->setParams(['order_id' => '999']);

        $this->getRequest()->setMethod('POST');
        $this->dispatch('backend/mollie/action/fetchOrderStatus');

        $json = json_decode($this->getResponse()->getContent(), true);

        $this->assertEquals(200, $this->getResponse()->getStatusCode());
        $this->assertTrue($json['success']);
        $this->assertEquals('[TEST] Test successful', $json['status']);
    }

    public function testReturnsAnErrorWhenSomethingFails(): void
    {
        $mollieHelperMock = $this->createMock(MollieHelper::class);
        $mollieHelperMock->method('orderHasUpdate')->willReturn(true);
        $mollieHelperMock->expects($this->once())
            ->method('processTransaction')
            ->willThrowException(new LocalizedException(__('[TEST] An error occured')));

        $this->_objectManager->addSharedInstance($mollieHelperMock, MollieHelper::class);

        $this->getRequest()->setParams(['order_id' => '999']);

        $this->getRequest()->setMethod('POST');
        $this->dispatch('backend/mollie/action/fetchOrderStatus');

        $json = json_decode($this->getResponse()->getContent(), true);

        $this->assertEquals(503, $this->getResponse()->getStatusCode());
        $this->assertTrue($json['error']);
        $this->assertEquals('[TEST] An error occured', $json['msg']);
    }

    public function testSendsASuccessMessage(): void
    {
        $mollieHelperMock = $this->createMock(MollieHelper::class);
        $mollieHelperMock->method('orderHasUpdate')->willReturn(true);
        $mollieHelperMock->expects($this->once())
            ->method('processTransaction')
            ->with('999', 'webhook')
            ->willReturn($this->_objectManager->create(ProcessTransactionResponse::class, [
                'success' => true,
                'status' => '[TEST] Test successful',
                'order_id' => -999,
                'type' => 'webhook',
            ]));

        $this->_objectManager->addSharedInstance($mollieHelperMock, MollieHelper::class);

        $this->getRequest()->setParams(['order_id' => '999']);

        $this->getRequest()->setMethod('POST');
        $this->dispatch('backend/mollie/action/fetchOrderStatus');

        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_ERROR);
        $this->assertSessionMessages(
            $this->equalTo(['The latest status from Mollie has been retrieved']),
            MessageInterface::TYPE_SUCCESS,
        );
    }

    public function testReturnsA503WhenAnErrorIsPresent(): void
    {
        $mollieHelperMock = $this->createMock(MollieHelper::class);
        $mollieHelperMock->method('orderHasUpdate')->willReturn(true);
        $mollieHelperMock->expects($this->once())
            ->method('processTransaction')
            ->with('999', 'webhook')
            ->willReturn($this->_objectManager->create(ProcessTransactionResponse::class, [
                'success' => false,
                'status' => '[TEST] Test unsuccessful',
                'order_id' => -999,
                'type' => 'webhook',
            ]));

        $this->_objectManager->addSharedInstance($mollieHelperMock, MollieHelper::class);

        $this->getRequest()->setParams(['order_id' => '999']);

        $this->getRequest()->setMethod('POST');
        $this->dispatch('backend/mollie/action/fetchOrderStatus');

        $json = json_decode($this->getResponse()->getContent(), true);

        $this->assertEquals(503, $this->getResponse()->getStatusCode());
        $this->assertTrue($json['error']);
        $this->assertEquals('[TEST] Test unsuccessful', $json['msg']);
    }

    public function testDoesNothingWhenUpdateNotNeeded(): void
    {
        $mollieHelperMock = $this->createMock(MollieHelper::class);
        $mollieHelperMock->method('orderHasUpdate')->willReturn(false);
        $mollieHelperMock->expects($this->never())->method('processTransaction');

        $this->_objectManager->addSharedInstance($mollieHelperMock, MollieHelper::class);

        $this->getRequest()->setParams(['order_id' => '999']);

        $this->getRequest()->setMethod('POST');
        $this->dispatch('backend/mollie/action/fetchOrderStatus');
    }
}
