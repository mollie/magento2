<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Checkout;

use Exception;
use Magento\Checkout\Model\Session;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Message\MessageInterface;
use Magento\Framework\Phrase;

/**
 * The checkout page removes the `page.messages` container, so messages that are added right before a redirect to
 * the checkout have nothing to render them. They are recorded here as well, so that the payment method knows which
 * of the messages in the shared `mage-messages` cookie were produced by Mollie.
 */
class PaymentMethodMessages
{
    private const SESSION_KEY = 'mollie_payment_method_messages';

    public function __construct(
        private Session $checkoutSession,
        private ManagerInterface $messageManager,
    ) {}

    public function addNotice(Phrase $message): void
    {
        $this->messageManager->addNoticeMessage($message);

        $this->remember(MessageInterface::TYPE_NOTICE, $message);
    }

    public function addError(Phrase $message): void
    {
        $this->messageManager->addErrorMessage($message);

        $this->remember(MessageInterface::TYPE_ERROR, $message);
    }

    public function addException(Exception $exception, Phrase $message): void
    {
        $this->messageManager->addExceptionMessage($exception, $message);

        $this->remember(MessageInterface::TYPE_ERROR, $message);
    }

    /**
     * @return array<int, array{type: string, text: string}>
     */
    public function getAndClear(): array
    {
        return $this->checkoutSession->getData(self::SESSION_KEY, true) ?? [];
    }

    private function remember(string $type, Phrase $message): void
    {
        $messages = $this->checkoutSession->getData(self::SESSION_KEY) ?? [];
        $messages[] = ['type' => $type, 'text' => (string)$message];

        $this->checkoutSession->setData(self::SESSION_KEY, $messages);
    }
}
