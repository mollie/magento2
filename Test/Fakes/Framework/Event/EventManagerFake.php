<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Fakes\Framework\Event;

use Magento\Framework\Event\ManagerInterface;

class EventManagerFake implements ManagerInterface
{
    /**
     * @var array<string, callable>
     */
    private array $observers = [];

    public function __construct(
        private ManagerInterface $eventManager,
    ) {}

    public function givenObserverFor(string $eventName, callable $observer): self
    {
        $this->observers[$eventName] = $observer;

        return $this;
    }

    public function dispatch($eventName, array $data = []): void
    {
        $this->eventManager->dispatch($eventName, $data);

        if (isset($this->observers[$eventName])) {
            ($this->observers[$eventName])($data);
        }
    }
}
