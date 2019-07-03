<?php

namespace Mollie\Payment\Test\Integration;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\TestFramework\ObjectManager;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;

class TestCase extends PHPUnitTestCase
{
    /**
     * @var \Magento\Framework\App\ObjectManager
     */
    protected $objectManager;

    protected function setUp()
    {
        parent::setUp();

        $this->objectManager = ObjectManager::getInstance();
    }

    /**
     * @return OrderInterface
     */
    protected function loadOrder($incrementId)
    {
        /** @var SearchCriteriaBuilder $searchCriteriaBuilder */
        $searchCriteriaBuilder = $this->objectManager->create(SearchCriteriaBuilder::class);

        /** @var OrderRepositoryInterface $order */
        $orderRepository = $this->objectManager->create(OrderRepositoryInterface::class);

        $searchCriteria = $searchCriteriaBuilder->addFilter('increment_id', $incrementId, 'eq')->create();
        $orderList = $orderRepository->getList($searchCriteria)->getItems();

        return array_shift($orderList);
    }
}