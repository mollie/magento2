<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Mollie\SelfTests;

use Magento\Framework\Phrase;

abstract class AbstractSelfTest
{
    /**
     * @var array
     */
    protected $messages = [];

    abstract public function execute(): void;

    public function addMessage(string $type, Phrase $message): void
    {
        $this->messages[] = [
            'type' => $type,
            'message' => $message->render(),
        ];
    }

    public function getMessages(): array
    {
        return $this->messages;
    }
}
