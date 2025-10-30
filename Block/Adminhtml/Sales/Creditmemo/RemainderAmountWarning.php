<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Block\Adminhtml\Sales\Creditmemo;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\Data\CreditmemoInterface;
use Mollie\Payment\Service\Mollie\Order\ParseAdditionalData;

class RemainderAmountWarning extends Template
{
    public function __construct(
        Context $context,
        private readonly Registry $registry,
        private readonly PriceCurrencyInterface $priceCurrency,
        private readonly ParseAdditionalData $parseAdditionalData,
        array $data = [],
    ) {
        parent::__construct($context, $data);
    }

    public function toHtml(): string
    {
        /** @var CreditmemoInterface $creditmemo */
        $creditmemo = $this->registry->registry('current_creditmemo');
        if (!$creditmemo->getOrder() || !$creditmemo->getOrder()->getPayment()) {
            return '';
        }

        $additionalData = $this->parseAdditionalData->fromPayment($creditmemo->getOrder()->getPayment());
        if ($additionalData->getDetails() === null) {
            return '';
        }

        $remainderAmount = $additionalData->getDetails()->getRemainderAmount();
        if ($remainderAmount === null) {
            return '';
        }

        $message = __(
            'Warning: This order is (partially) paid using a voucher or giftcard. You can refund a maximum of %1.',
            $this->priceCurrency->format(
                $remainderAmount->getValue(),
                true,
                PriceCurrencyInterface::DEFAULT_PRECISION,
                $creditmemo->getStoreId(),
            ),
        );

        return '<br><div class="message message-warning warning">' . $message . '</div>';
    }
}
