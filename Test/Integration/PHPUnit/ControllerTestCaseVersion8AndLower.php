<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration;

use Magento\TestFramework\TestCase\AbstractController;

class ControllerTestCase extends AbstractController
{
    protected function setUp()
    {
        parent::setup();

        $this->setUpWithoutVoid();
    }

    protected function setUpWithoutVoid()
    {
    }

    public function assertStringContainsString($expected, $actual, $message = null)
    {
        $this->assertContains($expected, $actual, $message);
    }
}
