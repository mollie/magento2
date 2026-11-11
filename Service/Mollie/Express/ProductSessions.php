<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Express;

use Magento\Checkout\Model\Session as CheckoutSession;

/**
 * Remembers which Mollie session belongs to which payment token for the product page, where there is no saved cart
 * to keep the session id on. Only the most recent sessions are kept, as every change on the product page starts a
 * new one.
 */
class ProductSessions
{
    private const SESSION_KEY = 'mollie_express_product_sessions';
    private const MAXIMUM_SESSIONS = 10;

    public function __construct(
        private readonly CheckoutSession $checkoutSession,
    ) {}

    public function remember(string $paymentToken, string $sessionId): void
    {
        $sessions = array_slice(
            [$paymentToken => $sessionId] + $this->getSessions(),
            0,
            self::MAXIMUM_SESSIONS,
            true,
        );

        $this->checkoutSession->setData(self::SESSION_KEY, $sessions);
    }

    public function find(string $paymentToken): ?string
    {
        return $this->getSessions()[$paymentToken] ?? null;
    }

    /**
     * @return array<string, string>
     */
    private function getSessions(): array
    {
        $sessions = $this->checkoutSession->getData(self::SESSION_KEY);

        return is_array($sessions) ? $sessions : [];
    }
}
