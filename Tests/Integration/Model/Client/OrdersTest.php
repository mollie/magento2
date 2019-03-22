<?php

namespace Mollie\Payment\Tests\Unit\Model\Client;

use Magento\Sales\Api\Data\OrderAddressInterface;
use Magento\TestFramework\ObjectManager;
use Mollie\Payment\Model\Client\Orders;
use PHPUnit\Framework\TestCase;

class OrdersTest extends TestCase
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    protected function setUp()
    {
        parent::setUp();

        $this->objectManager = ObjectManager::getInstance();
    }

    public function testRemovesEmptySpaceFromThePrefix()
    {
        /** @var Orders $instance */
        $instance = $this->objectManager->get(Orders::class);

        /** @var OrderAddressInterface $address */
        $address = $this->objectManager->get(OrderAddressInterface::class);

        $address->setPrefix('     ');

        $result = $instance->getAddressLine($address);

        $this->assertEmpty($result['title']);
    }
}