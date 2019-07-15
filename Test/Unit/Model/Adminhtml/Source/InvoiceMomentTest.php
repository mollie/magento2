<?php

namespace Mollie\Payment\Test\Unit\Model\Adminhtml\Source;

use Mollie\Payment\Model\Adminhtml\Source\InvoiceMoment;
use Mollie\Payment\Test\Unit\UnitTestCase;

class InvoiceMomentTest extends UnitTestCase
{
    public function containsTheCorrectOptionsProvider()
    {
        return [
            ['authorize'],
            ['shipment'],
        ];
    }

    /**
     * @dataProvider containsTheCorrectOptionsProvider
     */
    public function testContainsTheCorrectOptions($expected)
    {
        $instance = $this->objectManager->getObject(InvoiceMoment::class);

        $result = $instance->toOptionArray();

        $values = array_map(function ($value) { return $value['value']; }, $result);

        $this->assertContains($expected, $values);
    }
}
