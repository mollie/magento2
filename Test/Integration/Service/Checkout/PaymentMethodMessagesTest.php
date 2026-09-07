<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Checkout;

use Exception;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Message\MessageInterface;
use Mollie\Payment\Service\Checkout\PaymentMethodMessages;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class PaymentMethodMessagesTest extends IntegrationTestCase
{
    private PaymentMethodMessages $instance;

    protected function setUp(): void
    {
        parent::setUp();

        $this->instance = $this->objectManager->create(PaymentMethodMessages::class);
        $this->instance->getAndClear();
        $this->getMessageManagerTexts();
    }

    public function testRemembersTheTypeAndTextOfEveryMessageItAdds(): void
    {
        $this->instance->addNotice(__('Payment canceled, please try again.'));
        $this->instance->addError(__('Transaction failed.'));

        $this->assertSame([
            ['type' => MessageInterface::TYPE_NOTICE, 'text' => 'Payment canceled, please try again.'],
            ['type' => MessageInterface::TYPE_ERROR, 'text' => 'Transaction failed.'],
        ], $this->instance->getAndClear());
    }

    public function testForgetsTheMessagesOnceTheyAreRead(): void
    {
        $this->instance->addError(__('Transaction failed.'));
        $this->instance->getAndClear();

        $this->assertSame([], $this->instance->getAndClear());
    }

    public function testReturnsAnEmptyArrayWhenNothingWasAdded(): void
    {
        $this->assertSame([], $this->instance->getAndClear());
    }

    public function testAlsoAddsTheMessageToTheMessageManager(): void
    {
        $this->instance->addError(__('Transaction failed.'));

        $this->assertSame(['Transaction failed.'], $this->getMessageManagerTexts());
    }

    public function testRemembersTheAlternativeTextWhenAnExceptionIsAdded(): void
    {
        $this->instance->addException(
            new Exception('Connection refused'),
            __('There was an error checking the transaction status.')
        );

        $this->assertSame([
            ['type' => MessageInterface::TYPE_ERROR, 'text' => 'There was an error checking the transaction status.'],
        ], $this->instance->getAndClear());
    }

    /**
     * @return string[]
     */
    private function getMessageManagerTexts(): array
    {
        /** @var ManagerInterface $messageManager */
        $messageManager = $this->objectManager->get(ManagerInterface::class);

        return array_map(
            fn (MessageInterface $message): string => $message->getText(),
            $messageManager->getMessages(true)->getItems()
        );
    }
}
