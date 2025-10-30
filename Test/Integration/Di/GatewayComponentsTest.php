<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Di;

use Magento\Framework\ObjectManager\ConfigInterface;
use Magento\Payment\Block\Form;
use Magento\TestFramework\ObjectManager\Config;
use Mollie\Payment\Block\Form\Paymentlink;
use Mollie\Payment\Block\Form\Pointofsale;
use Mollie\Payment\Model\Methods\CreditcardVault;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use ReflectionObject;

class GatewayComponentsTest extends IntegrationTestCase
{
    public function testHasAValidatorPool(): void
    {
        /** @var Config $config */
        $config = $this->objectManager->get(ConfigInterface::class);

        foreach ($this->getMethods() as $method) {
            $name = $method['name'];

            $this->assertArrayHasKey('Mollie' . $name . 'ValidatorPool', $config->getVirtualTypes());
        }
    }

    public function testHasTheValidatorPoolConfigured(): void
    {
        $arguments = $this->getObjectManagerArguments();

        foreach ($this->getMethods() as $method) {
            $class = $method['class'];
            $name = $method['name'];
            $classArguments = $arguments[$class];

            $this->assertArrayHasKey('validatorPool', $classArguments, $name . ' does not have a ValidatorPool');
            $this->assertEquals('Mollie' . $name . 'ValidatorPool', $classArguments['validatorPool']['instance']);
        }
    }

    public function testHasACountryValidator(): void
    {
        /** @var Config $config */
        $config = $this->objectManager->get(ConfigInterface::class);

        foreach ($this->getMethods() as $method) {
            $name = $method['name'];

            $this->assertArrayHasKey('Mollie' . $name . 'CountryValidator', $config->getVirtualTypes());
        }
    }

    public function testCountryValidatorUsesCorrectConfiguration(): void
    {
        $arguments = $this->getObjectManagerArguments();

        /** @var Config $config */
        $config = $this->objectManager->get(ConfigInterface::class);

        foreach ($this->getMethods() as $method) {
            $name = $method['name'];

            $virtualTypes = $config->getVirtualTypes();

            $validatorName = 'Mollie' . $name . 'CountryValidator';
            $configName = 'Mollie' . $name . 'Config';
            $this->assertArrayHasKey($validatorName, $virtualTypes);

            $classArguments = $arguments[$validatorName];
            $this->assertEquals($configName, $classArguments['config']['instance']);
        }
    }

    public function testMethodHasCorrectFormBlockType(): void
    {
        $arguments = $this->getObjectManagerArguments();

        $blocks = [
            'default' => Form::class,
            'Paymentlink' => Paymentlink::class,
            'Pointofsale' => Pointofsale::class,
        ];

        foreach ($this->getMethods() as $method) {
            if ($method['name'] == 'CreditcardVault') {
                continue;
            }

            $class = $method['class'];
            $name = $method['name'];
            $classArguments = $arguments[$class];

            $expected = $blocks[$name] ?? $blocks['default'];

            $this->assertArrayHasKey('formBlockType', $classArguments, $name . ' does not have a formBlockType');
            $this->assertEquals($expected, $classArguments['formBlockType'], $name . ' have an incorrect formBlockType');
        }
    }

    public function testCreditcardVaultDoesNotHaveFormBlockType(): void
    {
        $arguments = $this->getObjectManagerArguments();

        $classArguments = $arguments[CreditcardVault::class];

        $this->assertArrayNotHasKey('formBlockType', $classArguments, 'CreditcardVault does have a formBlockType');
    }

    private function getObjectManagerArguments(): array
    {
        static $arguments = null;

        if ($arguments) {
            return $arguments;
        }

        /** @var Config $config */
        $config = $this->objectManager->get(ConfigInterface::class);

        $reflectionObject = new ReflectionObject($config);
        $reflectionProperty = $reflectionObject->getProperty('_arguments');
        $reflectionProperty->setAccessible(true);

        $arguments = $reflectionProperty->getValue($config);

        return $arguments;
    }

    private function getMethods(): array
    {
        $keys = array_keys($this->getObjectManagerArguments());

        $methods = array_filter($keys, function (int|string $key): bool {
            return strpos($key, 'Mollie\\Payment\\Model\\Methods') !== false;
        });

        return array_map(function (int|string $key): array {
            $parts = explode('\\', $key);

            return [
                'class' => $key,
                'name' => end($parts),
            ];
        }, $methods);
    }
}
