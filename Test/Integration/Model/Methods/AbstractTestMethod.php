<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Mollie\Api\Fake\MockResponse;
use Mollie\Api\Http\Requests\GetEnabledMethodsRequest;
use Mollie\Api\MollieApiClient;
use Mollie\Payment\Helper\General as MollieHelper;
use Mollie\Payment\Model\MollieConfigProvider;
use Mollie\Payment\Test\Fakes\Service\Mollie\FakeMollieApiClient;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use ReflectionClass;

abstract class AbstractTestMethod extends IntegrationTestCase
{
    /**
     * The class to test.
     */
    protected ?string $instance = null;

    /**
     * @var string
     */
    protected ?string $code = '';

    public function testHasAnExistingModel(): void
    {
        $this->assertTrue(class_exists($this->instance), 'We expect that the class ' . $this->instance . ' exists');
    }

    public function testHasTheCorrectCode(): void
    {
        /**
         * The parent constructor of this class calls the ObjectManager, which isn't available in unit tests. So skip
         * the constructor.
         */
        $reflection = new ReflectionClass($this->instance);
        $instance = $reflection->newInstanceWithoutConstructor();

        $this->assertEquals('mollie_methods_' . $this->code, $instance->getCode());
    }

    public function testIsListedAsActiveMethod(): void
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturn(1);

        $context = $this->objectManager->create(Context::class, [
            'scopeConfig' => $scopeConfig,
        ]);

        /** @var MollieHelper $helper */
        $helper = $this->objectManager->create(MollieHelper::class, [
            'context' => $context,
        ]);

        $methods = $helper->getAllActiveMethods(1);

        if ($this->code == 'paymentlink') {
            $this->assertArrayNotHasKey('mollie_methods_' . $this->code, $methods);

            return;
        }

        $this->assertArrayHasKey('mollie_methods_' . $this->code, $methods);
    }

    public function testThatTheMethodIsActive(): void
    {
        $mollieHelperMock = $this->createMock(MollieHelper::class);
        $mollieHelperMock->method('getOrderAmountByQuote')->willReturn(['value' => 100, 'currency' => 'EUR']);

        $client = MollieApiClient::fake([
            GetEnabledMethodsRequest::class => MockResponse::ok($this->getMethodListResponse()),
        ]);

        /** @var FakeMollieApiClient $fakeMollieApiClient */
        $fakeMollieApiClient = $this->objectManager->get(FakeMollieApiClient::class);
        $fakeMollieApiClient->setInstance($client);
        $this->objectManager->addSharedInstance($fakeMollieApiClient, \Mollie\Payment\Service\Mollie\MollieApiClient::class);

        /** @var MollieConfigProvider $instance */
        $instance = $this->objectManager->create(MollieConfigProvider::class, [
            'mollieHelper' => $mollieHelperMock,
        ]);
        $methods = $instance->getActiveMethods();

        $this->assertArrayHasKey('mollie_methods_' . $this->code, $methods);
        $this->assertEquals(
            'https://mollie.com/external/icons/payment-methods/' . $this->code . '%402x.png',
            $methods['mollie_methods_' . $this->code]['image'],
        );
    }

    private function getMethodListResponse(): string
    {
        return '{
  "count": 2,
  "_embedded": {
    "methods": [
      {
        "resource": "method",
        "id": "ideal",
        "description": "iDEAL",
        "minimumAmount": {
          "value": "0.01",
          "currency": "EUR"
        },
        "maximumAmount": {
          "value": "50000.00",
          "currency": "EUR"
        },
        "image": {
          "size1x": "https://mollie.com/external/icons/payment-methods/ideal.png",
          "size2x": "https://mollie.com/external/icons/payment-methods/ideal%402x.png",
          "svg": "https://mollie.com/external/icons/payment-methods/ideal.svg"
        },
        "status": "activated"
      },
      {
        "resource": "method",
        "id": "' . $this->code . '",
        "description": "Credit card",
        "minimumAmount": {
          "value": "0.01",
          "currency": "EUR"
        },
        "maximumAmount": {
          "value": "2000.00",
          "currency": "EUR"
        },
        "image": {
          "size1x": "https://mollie.com/external/icons/payment-methods/' . $this->code . '.png",
          "size2x": "https://mollie.com/external/icons/payment-methods/' . $this->code . '%402x.png",
          "svg": "https://mollie.com/external/icons/payment-methods/' . $this->code . '.svg"
        },
        "status": "activated"
      }
    ]
  },
  "_links": {
    "self": {
      "href": "...",
      "type": "application/hal+json"
    },
    "documentation": {
      "href": "...",
      "type": "text/html"
    }
  }
}
';
    }
}
