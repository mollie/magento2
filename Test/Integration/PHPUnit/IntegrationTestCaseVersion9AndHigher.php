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
use Magento\TestFramework\ObjectManager;
use Mollie\Payment\Test\Integration\PHPUnit\IntegrationTestCaseTrait;
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
}
