<?php

namespace Mollie\Payment\Controller\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Address as OrderAddress;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Model\Order\Payment;
use Magento\Store\Model\StoreManagerInterface;
use Magento\TestFramework\Request;
use Magento\TestFramework\TestCase\AbstractController as ControllerTestCase;
use Mollie\Payment\Model\Methods\Ideal;
use Mollie\Payment\Test\Fakes\Model\Methods\IdealFake;

class RedirectTest extends ControllerTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (getenv('CI')) {
            $this->markTestSkipped('Fails on CI');
        }
    }

    /**
     * @magentoConfigFixture current_store payment/mollie_general/cancel_failed_orders 1
     * @magentoDbIsolation disabled
     * @magentoDataFixture createOrder
     */
    public function testUncancelsTheOrder()
    {
        $this->_objectManager->get(Session::class)->setLastRealOrderId('100000001');

        $this->_objectManager->configure([
            'preferences' => [
                Ideal::class => IdealFake::class,
            ],
        ]);

        $this->getRequest()->setMethod(Request::METHOD_POST);
        $this->dispatch('mollie/checkout/redirect');

        $newOrder = $this->loadOrder();
        $this->assertEquals(Order::STATE_CANCELED, $newOrder->getState());
    }

    /**
     * @magentoConfigFixture current_store payment/mollie_general/cancel_failed_orders 0
     * @magentoDbIsolation disabled
     * @magentoDataFixture createOrder
     */
    public function testDoesNotCancelWhenDisabled()
    {
        $this->_objectManager->get(Session::class)->setLastRealOrderId('100000001');

        $this->_objectManager->configure([
            'preferences' => [
                Ideal::class => IdealFake::class,
            ],
        ]);

        $this->getRequest()->setMethod(Request::METHOD_POST);
        $this->dispatch('mollie/checkout/redirect');

        $newOrder = $this->loadOrder();
        $this->assertNotEquals(Order::STATE_CANCELED, $newOrder->getState());
    }

    private function loadOrder()
    {
        $criteria = $this->_objectManager->create(SearchCriteriaBuilder::class)
            ->addFilter('increment_id', '100000001', 'eq')->create();

        $repository = $this->_objectManager->get(OrderRepositoryInterface::class);
        $result = $repository->getList($criteria)->getItems();
        return array_shift($result);
    }

    /**
     * This is a copy of dev/tests/integration/testsuite/Magento/Sales/_files/order.php,
     * but with the Mollie payment method.
     *
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    static public function createOrder()
    {
        // @TODO
        return;

        require BP . '/dev/tests/integration/testsuite/Magento/Sales/_files/default_rollback.php';
        require BP . '/dev/tests/integration/testsuite/Magento/Catalog/_files/product_simple.php';
        /** @var \Magento\Catalog\Model\Product $product */

        $addressData = include BP . '/dev/tests/integration/testsuite/Magento/Sales/_files/address_data.php';

        $objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

        $billingAddress = $objectManager->create(OrderAddress::class, ['data' => $addressData]);
        $billingAddress->setAddressType('billing');

        $shippingAddress = clone $billingAddress;
        $shippingAddress->setId(null)->setAddressType('shipping');

        /** @var Payment $payment */
        $payment = $objectManager->create(Payment::class);
        $payment->setMethod('mollie_methods_ideal');

        /** @var OrderItem $orderItem */
        $orderItem = $objectManager->create(OrderItem::class);
        $orderItem->setProductId($product->getId())
            ->setQtyOrdered(2)
            ->setBasePrice($product->getPrice())
            ->setPrice($product->getPrice())
            ->setRowTotal($product->getPrice())
            ->setProductType('simple');

        /** @var Order $order */
        $order = $objectManager->create(Order::class);
        $order->setIncrementId('100000001')
            ->setState(Order::STATE_PROCESSING)
            ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_PROCESSING))
            ->setSubtotal(100)
            ->setGrandTotal(100)
            ->setBaseSubtotal(100)
            ->setBaseGrandTotal(100)
            ->setCustomerIsGuest(true)
            ->setCustomerEmail('customer@null.com')
            ->setBillingAddress($billingAddress)
            ->setShippingAddress($shippingAddress)
            ->setStoreId($objectManager->get(StoreManagerInterface::class)->getStore()->getId())
            ->addItem($orderItem)
            ->setPayment($payment);

        /** @var OrderRepositoryInterface $orderRepository */
        $orderRepository = $objectManager->create(OrderRepositoryInterface::class);
        $orderRepository->save($order);
    }
}
