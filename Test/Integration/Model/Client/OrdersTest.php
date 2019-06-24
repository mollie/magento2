<?php

namespace Mollie\Payment\Model\Client;

use Magento\Sales\Api\Data\OrderInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase;

class OrdersTest extends TestCase
{
    /**
     * This key is invalid on purpose, as we can't work our way around the `new \Mollie\Api\MollieApiClient()` call.
     * It turns out that an invalid key also throws an exception, which is what we actually want in this case.
     *
     * @magentoConfigFixture default_store payment/mollie_general/apikey_test test_TEST_API_KEY
     * @magentoConfigFixture default_store payment/mollie_general/type test
     */
    public function testCancelOrderThrowsAnExceptionWithTheOrderIdIncluded()
    {
        $om = ObjectManager::getInstance();

        /** @var Orders $instance */
        $instance = $om->create(Orders::class);

        /** @var OrderInterface $order */
        $order = $om->create(OrderInterface::class);
        $order->setEntityId(999);
        $order->setMollieTransactionId('MOLLIE-999');

        try {
            $instance->cancelOrder($order);
        } catch (\Magento\Framework\Exception\LocalizedException $exception) {
            $this->assertContains('Order ID: 999', $exception->getMessage());
            return;
        }

        $this->fail('We expected an exception but this was not thrown');
    }
}
