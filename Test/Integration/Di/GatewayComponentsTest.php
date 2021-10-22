<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Di;

use Magento\Framework\ObjectManager\ConfigInterface;
use Magento\TestFramework\ObjectManager\Config;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class GatewayComponentsTest extends IntegrationTestCase
{
    public function testHasAValidatorPool()
    {
        /** @var Config $config */
        $config = $this->objectManager->get(ConfigInterface::class);

        foreach ($this->getMethods() as $method) {
            $name = $method['name'];

            $this->assertArrayHasKey('Mollie' . $name . 'ValidatorPool', $config->getVirtualTypes());
        }
    }

    public function testHasTheValidatorPoolConfigured()
    {
        $arguments = $this->getObjectManagerArguments();

        foreach ($this->getMethods() as $method) {
            $class = $method['class'];
            $name  = $method['name'];
            $classArguments = $arguments[$class];

            $this->assertArrayHasKey('validatorPool', $classArguments, $name . ' does not have a ValidatorPool');
            $this->assertEquals('Mollie' . $name . 'ValidatorPool', $classArguments['validatorPool']['instance']);
        }
    }

    public function testHasACountryValidator()
    {
        /** @var Config $config */
        $config = $this->objectManager->get(ConfigInterface::class);

        foreach ($this->getMethods() as $method) {
            $name = $method['name'];

            $this->assertArrayHasKey('Mollie' . $name . 'CountryValidator', $config->getVirtualTypes());
        }
    }

    public function testCountryValidatorUsesCorrectConfiguration()
    {
        $arguments = $this->getObjectManagerArguments();

        /** @var Config $config */
        $config = $this->objectManager->get(ConfigInterface::class);

        foreach ($this->getMethods() as $method) {
            $name  = $method['name'];

            $virtualTypes = $config->getVirtualTypes();

            $validatorName = 'Mollie' . $name . 'CountryValidator';
            $configName = 'Mollie' . $name . 'Config';
            $this->assertArrayHasKey($validatorName, $virtualTypes);

            $classArguments = $arguments[$validatorName];
            $this->assertEquals($configName, $classArguments['config']['instance']);
        }
    }

    private function getObjectManagerArguments(): array
    {
        static $arguments = null;

        if ($arguments) {
            return $arguments;
        }

        /** @var Config $config */
        $config = $this->objectManager->get(ConfigInterface::class);

        $reflectionObject = new \ReflectionObject($config);
        $reflectionProperty = $reflectionObject->getProperty('_arguments');
        $reflectionProperty->setAccessible(true);

        $arguments = $reflectionProperty->getValue($config);

        return $arguments;
    }

    private function getMethods(): array
    {
        $keys = array_keys($this->getObjectManagerArguments());

        $methods = array_filter($keys, function ($key) {
            return strpos($key, 'Mollie\\Payment\\Model\\Methods') !== false;
        });

        return array_map(function ($key) {
            $parts = explode('\\', $key);

            return [
                'class' => $key,
                'name' => end($parts),
            ];
        }, $methods);
    }
}
