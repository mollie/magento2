<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Block\Adminhtml\Sales\Order;

use Magento\Framework\Phrase;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Adminhtml\Source\CaptureMoment;
use Mollie\Payment\Service\Mollie\Order\CanUseManualCapture;
use Mollie\Payment\Service\Mollie\Order\SupportsPartialCapture;

class PartialCaptureNotSupportedNotice extends Template
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        private readonly Registry $registry,
        private readonly Config $config,
        private readonly CanUseManualCapture $canUseManualCapture,
        private readonly SupportsPartialCapture $supportsPartialCapture,
        array $data = [],
    ) {
        parent::__construct($context, $data);
    }

    public function toHtml(): string
    {
        if (!$this->shouldShowNotice()) {
            return '';
        }

        return parent::toHtml();
    }

    public function getMessage(): Phrase
    {
        return __(
            'Please note: %1 does not support partial captures. Only the complete order can be captured, so a partial invoice or shipment will be refused.',
            $this->getMethodTitle($this->getOrder()),
        );
    }

    private function shouldShowNotice(): bool
    {
        $order = $this->getOrder();
        if ($order === null || $order->getPayment() === null) {
            return false;
        }

        if (!$this->canUseManualCapture->execute($order) || $this->supportsPartialCapture->execute($order)) {
            return false;
        }

        return $this->capturesOnThisPage($order);
    }

    private function capturesOnThisPage(OrderInterface $order): bool
    {
        $captureMoment = (string) $this->getData('capture_moment');
        if (!in_array($captureMoment, [CaptureMoment::ON_INVOICE, CaptureMoment::ON_SHIPMENT], true)) {
            return false;
        }

        return $this->config->whenToCapture(
            $order->getPayment()->getMethod(),
            storeId($order->getStoreId()),
        ) === $captureMoment;
    }

    private function getMethodTitle(OrderInterface $order): string
    {
        $method = $order->getPayment()->getMethod();

        return $this->config->getMethodTitle($method, storeId($order->getStoreId())) ?: $method;
    }

    private function getOrder(): ?OrderInterface
    {
        return $this->registry->registry((string) $this->getData('registry_key'))?->getOrder();
    }
}
