<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Block\Info\Method;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\View\Element\Template;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Api\Data\PaymentInterface;
use Mollie\Payment\Service\Mollie\Order\AdditionalData;
use Mollie\Payment\Service\Mollie\Order\ParseAdditionalData;

/**
 * @method PaymentInterface getInfo()
 */
class GiftcardInfo extends Template
{
    protected $_template = 'Mollie_Payment::info/method/giftcard_info.phtml';
    /**
     * @var ParseAdditionalData
     */
    private $parseAdditionalData;
    /**
     * @var AdditionalData|null
     */
    private $additionalData = null;
    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;
    /**
     * @var Data
     */
    private $paymentData;

    public function __construct(
        Template\Context $context,
        Data $paymentData,
        PriceCurrencyInterface $priceCurrency,
        ParseAdditionalData $parseAdditionalData,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->parseAdditionalData = $parseAdditionalData;
        $this->priceCurrency = $priceCurrency;
        $this->paymentData = $paymentData;
    }

    public function formatPrice(float $price): string
    {
        return $this->priceCurrency->format($price, false);
    }

    public function getAdditionalData(): AdditionalData
    {
        if ($this->additionalData !== null) {
            return $this->additionalData;
        }

        return $this->additionalData = $this->parseAdditionalData->fromPayment($this->getInfo());
    }

    public function getRemainderMethodInstance(): MethodInterface
    {
        return $this->paymentData->getMethodInstance('mollie_methods_' . $this->getAdditionalData()->getDetails()->getRemainderMethod());
    }

    /**
     * @return AdditionalData\Giftcard[]
     */
    public function getUsedGiftcards(): array
    {
        $additionalData = $this->getAdditionalData();

        return $additionalData->getDetails()->getGiftcards() ?: [];
    }

    public function hasRemainderAmount(): bool
    {
        $additionalData = $this->getAdditionalData();

        return $additionalData->getDetails()->getRemainderAmount() !== null;
    }
}
