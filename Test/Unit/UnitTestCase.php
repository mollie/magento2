<?php
namespace Mollie\Payment\Test\Unit;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

class UnitTestCase extends TestCase
{
    /**
     * @var ObjectManager
     */
    protected $objectManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->objectManager = new ObjectManager($this);
        $this->setUpWithoutVoid();
    }

    protected function setUpWithoutVoid()
    {
    }
}
