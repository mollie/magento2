<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Model\Adminhtml\Backend;

use Mollie\Payment\Model\Adminhtml\Backend\VerifiyPaymentFee;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class VerifiyPaymentFeeTest extends IntegrationTestCase
{
    public function testReplacesCommasWithADot(): void
    {
        /** @var VerifiyPaymentFee $instance */
        $instance = $this->objectManager->create(VerifiyPaymentFee::class);

        $instance->setValue('1,23');

        $instance->beforeSave();

        $this->assertSame('1.23', $instance->getValue());
    }

    public function testStripsPercentageSigns(): void
    {
        /** @var VerifiyPaymentFee $instance */
        $instance = $this->objectManager->create(VerifiyPaymentFee::class);

        $instance->setValue('1,23%');

        $instance->beforeSave();

        $this->assertSame('1.23', $instance->getValue());
    }
}
