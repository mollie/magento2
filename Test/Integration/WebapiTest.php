<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration;

use Magento\Webapi\Model\Rest\Swagger\Generator;

class WebapiTest extends IntegrationTestCase
{
    public function testCanLoadWebapi(): void
    {
        $this->expectNotToPerformAssertions();

        // This is used by the /swagger endpoint. If this fails, the /swagger endpoint will fail as well.
        $instance = $this->objectManager->create(Generator::class);
        $instance->getListOfServices();
    }
}
