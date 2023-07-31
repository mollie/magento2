<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\TestFramework\Annotation\DataFixture;
use Magento\TestFramework\ObjectManager;
use Mollie\Payment\Service\Mollie\MollieApiClient;
use Mollie\Payment\Service\OrderLockService;
use Mollie\Payment\Test\Fakes\Service\Mollie\FakeMollieApiClient;
use Mollie\Payment\Test\Fakes\Service\OrderLockServiceFake;
use Mollie\Payment\Test\Integration\PHPUnit\IntegrationTestCaseTrait;
use Magento\TestFramework\Workaround\Override\Fixture\Resolver;
use PHPUnit\Framework\TestCase;

class IntegrationTestCase extends TestCase
{
    use IntegrationTestCaseTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = ObjectManager::getInstance();
        $this->setUpWithoutVoid();
    }

    protected function setUpWithoutVoid()
    {
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->tearDownWithoutVoid();
    }

    protected function tearDownWithoutVoid()
    {
    }

    /**
     * @param $orderId
     * @return \Magento\Sales\Model\Order
     */
    public function loadOrderById($orderId)
    {
        $repository = $this->objectManager->get(OrderRepositoryInterface::class);
        $builder = $this->objectManager->create(SearchCriteriaBuilder::class);
        $searchCriteria = $builder->addFilter('increment_id', $orderId, 'eq')->create();

        $orderList = $repository->getList($searchCriteria)->getItems();

        return array_shift($orderList);
    }

    /**
     * Load a custom fixture in the Test/Fixtures folder, and make it think it's inside the
     * `dev/test/integration/testsuite` folder so it can rely on other fixtures.
     *
     * @param $path
     * @throws \Exception
     */
    public function loadFixture($path)
    {
        $cwd = getcwd();

        $fullPath = __DIR__ . '/../../Fixtures/' . $path;
        if (!file_exists($fullPath)) {
            throw new \Exception('The path "' . $fullPath . '" does not exists');
        }

        if (class_exists(Resolver::class)) {
            $resolver = Resolver::getInstance();
            $resolver->setCurrentFixtureType(DataFixture::ANNOTATION);
        }

        chdir($this->getRootDirectory() . '/dev/tests/integration/testsuite/');
        require $fullPath;
        chdir($cwd);
    }

    public function getRootDirectory()
    {
        static $path;

        if (!$path) {
            $directoryList = $this->objectManager->get(DirectoryList::class);
            $path = $directoryList->getRoot();
        }

        return $path;
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

    public function loadFakeMollieApiClient(): FakeMollieApiClient
    {
        $client = $this->objectManager->create(FakeMollieApiClient::class);

        $this->objectManager->addSharedInstance($client, MollieApiClient::class);

        return $client;
    }

    public function loadFakeOrderLockService(): void
    {
        $service = $this->objectManager->create(OrderLockServiceFake::class);

        $this->objectManager->addSharedInstance($service, OrderLockService::class);
    }
}
