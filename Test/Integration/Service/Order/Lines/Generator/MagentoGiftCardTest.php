<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order\Lines\Generator;

use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Service\Order\Lines\Generator\MagentoGiftCard;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class MagentoGiftCardTest extends IntegrationTestCase
{
    public function testDoesNothingWhenThereIsNoGiftCardAmount(): void
    {
        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);

        /** @var MagentoGiftCard $instance */
        $instance = $this->objectManager->create(MagentoGiftCard::class);

        $this->assertCount(0, $instance->process($order, []));
    }

    public function testBuildsAGiftCardLineFromStringValuesFromTheDatabase(): void
    {
        /** @var OrderInterface $order */
        $order = $this->objectManager->create(OrderInterface::class);
        $order->setBaseCurrencyCode('EUR');
        $order->setOrderCurrencyCode('EUR');
        $order->setData('gift_cards_amount', '10.0000');
        $order->setData('base_gift_cards_amount', '10.0000');

        /** @var MagentoGiftCard $instance */
        $instance = $this->objectManager->create(MagentoGiftCard::class);

        $result = $instance->process($order, []);

        $this->assertCount(1, $result);
        $line = end($result);
        $this->assertEquals('-10.00', $line['unitPrice']['value']);
        $this->assertEquals('-10.00', $line['totalAmount']['value']);
        $this->assertEquals('0.00', $line['vatAmount']['value']);
    }
}
