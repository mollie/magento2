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

class VoucherWarning extends Template
{
    public function __construct(
        Context $context,
        private Registry $registry,
        private PriceCurrencyInterface $priceCurrency,
        array $data = [],
    ) {
        parent::__construct($context, $data);
    }

    public function toHtml()
    {
        /** @var CreditmemoInterface $creditmemo */
        $creditmemo = $this->registry->registry('current_creditmemo');
        if (!$creditmemo->getOrder() || !$creditmemo->getOrder()->getPayment()) {
            return '';
        }

        $remainderAmount = $creditmemo->getOrder()->getPayment()->getAdditionalInformation('remainder_amount');
        if (!$remainderAmount) {
            return '';
        }

        $message = __(
            'Warning: This order is (partially) paid using a voucher. You can refund a maximum of %1.',
            $this->priceCurrency->format(
                $remainderAmount,
                true,
                PriceCurrencyInterface::DEFAULT_PRECISION,
                $creditmemo->getStoreId(),
            ),
        );

        return '<br><div class="message message-warning warning">' . $message . '</div>';
    }
}
