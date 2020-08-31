<?php

namespace Mollie\Payment\Test\Ingegration\Model\Methods;

use Magento\Framework\DataObject;
use Mollie\Payment\Model\Methods\Paymentlink;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class PaymentlinkTest extends IntegrationTestCase
{
    protected $instance = Paymentlink::class;

    protected $code = 'paymentlink';

    /**
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Mollie\Api\Exceptions\ApiException
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_methods_paymentlink/order_status_new newPendingStatus
     */
    public function testSetsTheCorrectStatus()
    {
        $order = $this->loadOrderById('100000001');

        $status = 'newPendingStatus';

        /** @var Paymentlink $instance */
        $instance = $this->objectManager->get(Paymentlink::class);

        $instance->setInfoInstance($order->getPayment());

        $statusObject = new DataObject();
        $instance->initialize('new', $statusObject);

        $this->assertEquals($status, $statusObject->getData('status'));
    }
}
