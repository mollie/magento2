<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Webapi;

use Magento\Webapi\Model\Config\ClassReflector;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

abstract class AbstractTestWebApi extends IntegrationTestCase
{
    /**
     * @var string
     */
    protected $class;

    /**
     * @var string[]
     */
    protected $methods;

    public function testValidatesForSwagger()
    {
        if (!$this->class || !$this->methods) {
            throw new \Exception('Please set the $class and $method variables');
        }

        /** @var ClassReflector $reflector */
        $reflector = $this->objectManager->get(ClassReflector::class);

        $implementations = array_merge([$this->class], class_implements($this->class));
        foreach ($implementations as $implementation) {
            $reflector->reflectClassMethods($implementation, $this->methods);
            $this->addToAssertionCount(1);
        }
    }
}
