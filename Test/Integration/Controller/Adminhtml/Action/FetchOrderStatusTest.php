<?php

namespace Mollie\Payment\Controller\Adminhtml\Action;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\MessageInterface;
use Magento\TestFramework\TestCase\AbstractBackendController;
use Mollie\Payment\Model\Mollie as MollieHelper;

class FetchOrderStatusTest extends AbstractBackendController
{
    public function testProcessesTheTransaction()
    {
        $mollieHelperMock = $this->createMock(MollieHelper::class);
        $mollieHelperMock->method('orderHasUpdate')->willReturn(true);
        $mollieHelperMock->expects($this->once())
            ->method('processTransaction')
            ->with('999', 'webhook')
            ->willReturn([
                'error' => false,
                'msg' => '[TEST] Test successfull',
            ])
        ;

        $this->_objectManager->addSharedInstance($mollieHelperMock, MollieHelper::class);

        $this->getRequest()->setParams(['order_id' => '999']);

        $this->dispatch('backend/mollie/action/fetchOrderStatus');

        $json = \json_decode($this->getResponse()->getContent(), JSON_OBJECT_AS_ARRAY);

        $this->assertEquals(200, $this->getResponse()->getStatusCode());
        $this->assertFalse($json['error']);
        $this->assertEquals('[TEST] Test successfull', $json['msg']);
    }

    public function testReturnsAnErrorWhenSomethingFails()
    {
        $mollieHelperMock = $this->createMock(MollieHelper::class);
        $mollieHelperMock->method('orderHasUpdate')->willReturn(true);
        $mollieHelperMock->expects($this->once())
            ->method('processTransaction')
            ->willThrowException(new LocalizedException(__('[TEST] An error occured')))
        ;

        $this->_objectManager->addSharedInstance($mollieHelperMock, MollieHelper::class);

        $this->getRequest()->setParams(['order_id' => '999']);

        $this->dispatch('backend/mollie/action/fetchOrderStatus');

        $json = \json_decode($this->getResponse()->getContent(), JSON_OBJECT_AS_ARRAY);

        $this->assertEquals(503, $this->getResponse()->getStatusCode());
        $this->assertTrue($json['error']);
        $this->assertEquals('[TEST] An error occured', $json['msg']);
    }

    public function testSendsASuccessMessage()
    {
        $mollieHelperMock = $this->createMock(MollieHelper::class);
        $mollieHelperMock->method('orderHasUpdate')->willReturn(true);
        $mollieHelperMock->expects($this->once())
            ->method('processTransaction')
            ->with('999', 'webhook')
            ->willReturn([
                'error' => false,
                'msg' => '[TEST] Test successfull',
            ])
        ;

        $this->_objectManager->addSharedInstance($mollieHelperMock, MollieHelper::class);

        $this->getRequest()->setParams(['order_id' => '999']);

        $this->dispatch('backend/mollie/action/fetchOrderStatus');

        $this->assertSessionMessages($this->isEmpty(), MessageInterface::TYPE_ERROR);
        $this->assertSessionMessages(
            $this->equalTo(['The latest status from Mollie has been retrieved']),
            MessageInterface::TYPE_SUCCESS
        );
    }

    public function testDoesNothingWhenUpdateNotNeeded()
    {
        $mollieHelperMock = $this->createMock(MollieHelper::class);
        $mollieHelperMock->method('orderHasUpdate')->willReturn(false);
        $mollieHelperMock->expects($this->never())->method('processTransaction');

        $this->_objectManager->addSharedInstance($mollieHelperMock, MollieHelper::class);

        $this->getRequest()->setParams(['order_id' => '999']);

        $this->dispatch('backend/mollie/action/fetchOrderStatus');
    }
}
