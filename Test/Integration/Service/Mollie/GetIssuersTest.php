<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Test\Integration\Service\Mollie;

use Magento\Framework\App\CacheInterface;
use Mollie\Api\MollieApiClient;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Service\Mollie\GetIssuers;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class GetIssuersTest extends IntegrationTestCase
{
    public function testDoesNotReturnTheImageWhenNotAvailable(): void
    {
        $method = new \stdClass();
        $method->name = 'Issuer name';
        $method->id = 'IDID';

        $mollieModelMock = $this->createMock(Mollie::class);
        $mollieModelMock->method('getIssuers')->willReturn([$method]);
        $mollieModelMock->method('getMollieApi')->willReturn(new MollieApiClient());

        $cacheMock = $this->createMock(CacheInterface::class);

        /** @var GetIssuers $instance */
        $instance = $this->objectManager->create(GetIssuers::class, [
            'mollieModel' => $mollieModelMock,
            'cache' => $cacheMock,
        ]);

        $result = $instance->getForGraphql(1, 'mollie_methods_ideal');

        $this->assertCount(1, $result);
        $this->assertArrayNotHasKey('image', $result[0]);
        $this->assertArrayNotHasKey('svg', $result[0]);
    }

    public function testReturnsTheImageWhenAvailable(): void
    {
        $method = new \stdClass();
        $method->name = 'Issuer name';
        $method->id = 'IDID';
        $method->image = new \stdClass();
        $method->image->size2x = 'image.png';
        $method->image->svg = 'image.svg';

        $mollieModelMock = $this->createMock(Mollie::class);
        $mollieModelMock->method('getIssuers')->willReturn([$method]);
        $mollieModelMock->method('getMollieApi')->willReturn(new MollieApiClient());

        $cacheMock = $this->createMock(CacheInterface::class);

        /** @var GetIssuers $instance */
        $instance = $this->objectManager->create(GetIssuers::class, [
            'mollieModel' => $mollieModelMock,
            'cache' => $cacheMock,
        ]);

        $result = $instance->getForGraphql(1, 'mollie_methods_ideal');

        $this->assertCount(1, $result);
        $this->assertArrayHasKey('image', $result[0]);
        $this->assertEquals('image.png', $result[0]['image']);

        $this->assertArrayHasKey('svg', $result[0]);
        $this->assertEquals('image.svg', $result[0]['svg']);
    }
}
