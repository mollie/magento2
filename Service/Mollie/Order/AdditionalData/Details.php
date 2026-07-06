<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Order\AdditionalData;

class Details
{
    /**
     * @var array|null
     */
    private $giftcards;
    /**
     * @var string|null
     */
    private $remainderMethod;
    /**
     * @var MollieAmount|null
     */
    private $remainderAmount;

    public function __construct(
        /* @var $giftcards Giftcard */
        ?array $giftcards,
        ?string $remainderMethod,
        ?MollieAmount $remainderAmount = null
    ) {
        $this->giftcards = $giftcards;
        $this->remainderMethod = $remainderMethod;
        $this->remainderAmount = $remainderAmount;
    }

    public function getGiftcards(): ?array
    {
        return $this->giftcards;
    }

    public function getRemainderAmount(): ?MollieAmount
    {
        return $this->remainderAmount;
    }

    public function getRemainderMethod(): ?string
    {
        return $this->remainderMethod;
    }
}
