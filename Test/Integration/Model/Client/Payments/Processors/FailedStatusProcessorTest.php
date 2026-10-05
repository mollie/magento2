<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Model\Client\Payments\Processors;

use Mollie\Payment\Model\Client\Payments\Processors\FailedStatusProcessor;
use Mollie\Payment\Model\Client\ProcessTransactionResponse;
use Mollie\Payment\Service\Mollie\PaymentFailureReason;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use Mollie\Payment\Test\Integration\MolliePaymentBuilder;

class FailedStatusProcessorTest extends IntegrationTestCase
{
    /**
     * @magentoDataFixture Magento/Sales/_files/order.php
     */
    public function testAddsTheFailureDetailsOfThePaymentToTheResponse(): void
    {
        $order = $this->loadOrderById('100000001');

        /** @var MolliePaymentBuilder $paymentBuilder */
        $paymentBuilder = $this->objectManager->create(MolliePaymentBuilder::class);
        $paymentBuilder->setAmount(100, 'USD');
        $paymentBuilder->setStatus('failed');
        $molliePayment = $paymentBuilder->build();
        $molliePayment->details = (object) [
            'failureReason' => 'card_declined',
            'failureMessage' => 'Your card was declined.',
        ];

        $result = $this->objectManager->get(FailedStatusProcessor::class)->process(
            $order,
            $molliePayment,
            'success',
            $this->objectManager->create(ProcessTransactionResponse::class, [
                'success' => true,
                'status' => 'failed',
                'order_id' => '-01',
                'type' => 'success',
            ]),
        );

        $this->assertSame(PaymentFailureReason::CardDeclined, $result->getPaymentFailure()->getReason());
        $this->assertSame('Your card was declined.', $result->getPaymentFailure()->getMessage());
    }
}
