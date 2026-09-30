<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Checkout;

use Magento\Framework\Escaper;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Adminhtml\Source\PaymentInstructionsStyle;
use Mollie\Payment\Model\Methods\Banktransfer;
use Mollie\Payment\Model\Methods\Multibanco;

class PaymentInstructions
{
    public const SUPPORTED_METHODS = [
        Banktransfer::CODE,
        Multibanco::CODE,
    ];

    private const CSS_CLASS_PER_STYLE = [
        PaymentInstructionsStyle::PLAIN => '',
        PaymentInstructionsStyle::INFO => 'message info',
        PaymentInstructionsStyle::WARNING => 'message warning',
        PaymentInstructionsStyle::SUCCESS => 'message success',
    ];

    public function __construct(
        private readonly Config $config,
        private readonly Escaper $escaper,
    ) {}

    public function isSupported(string $method): bool
    {
        return in_array($method, self::SUPPORTED_METHODS, true);
    }

    public function isEnabled(string $method, int $storeId): bool
    {
        return $this->isSupported($method) &&
            $this->config->paymentMethodInstructionsStyle($method, $storeId) !== PaymentInstructionsStyle::NONE;
    }

    public function getHtml(string $method, int $storeId): string
    {
        if (!$this->isEnabled($method, $storeId)) {
            return '';
        }

        return nl2br($this->escaper->escapeHtml(trim($this->config->paymentMethodInstructions($method, $storeId))));
    }

    public function getCssClass(string $method, int $storeId): string
    {
        return self::CSS_CLASS_PER_STYLE[$this->config->paymentMethodInstructionsStyle($method, $storeId)]
            ?? self::CSS_CLASS_PER_STYLE[PaymentInstructionsStyle::INFO];
    }
}
