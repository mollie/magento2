<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Order;

use Magento\Framework\Mail\Address;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\TestFramework\Mail\Template\TransportBuilderMock;
use Mollie\Payment\Service\Order\SecondChanceEmail;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class SecondChanceEmailTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoDataFixture Magento/Sales/_files/quote.php
     * @magentoConfigFixture default_store payment/mollie_general/second_chance_send_bcc_to example@mollie.com,example2@mollie.com
     * @return void
     */
    public function testAddsBcc(): void
    {
        $order = $this->getOrder();

        /** @var SecondChanceEmail $instance */
        $instance = $this->objectManager->create(SecondChanceEmail::class);
        $instance->send($order);

        /** @var TransportBuilderMock $transportBuilder */
        $transportBuilder = $this->objectManager->get(TransportBuilderMock::class);

        $bcc = null;
        // Magento 2.4.8 and later
        $message = $transportBuilder->getSentMessage();
        if (method_exists($message, 'getBcc')) {
            $addresses = [];
            $bccs = $message->getBcc();
            /** @var Address $address */
            foreach ($bccs as $address) {
                $addresses[] = $address->getEmail();
            }

            $bcc = implode(',', $addresses);
        }

        // Magento 2.4.7 and earlier
        if ($bcc === null) {
            $bcc = $message->getHeaders()['Bcc'];
        }

        $this->assertStringContainsString('example@mollie.com', $bcc);
        $this->assertStringContainsString('example2@mollie.com', $bcc);
    }

    public function getOrder(): OrderInterface
    {
        $order = $this->loadOrderById('100000001');

        // This is required for the payment token.
        /** @var CartInterface $cart */
        $cart = $this->objectManager->create(Quote::class);
        $cart->load('test01', 'reserved_order_id');
        $order->setQuoteId($cart->getId());

        return $order;
    }
}
