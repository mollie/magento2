<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Checkout\PaymentInstructions;

class PaymentInstructionsConfigProvider implements ConfigProviderInterface
{
    public function __construct(
        private readonly Config $config,
        private readonly StoreManagerInterface $storeManager,
        private readonly PaymentInstructions $paymentInstructions,
    ) {}

    /**
     * @return array{payment?: array{mollie: array{instructions: array<string, array{html: string, cssClass: string}>}}}
     */
    public function getConfig(): array
    {
        $storeId = (int) $this->storeManager->getStore()->getId();
        if (!$this->config->isModuleEnabled($storeId)) {
            return [];
        }

        $instructions = $this->getInstructionsPerMethod($storeId);
        if ($instructions === []) {
            return [];
        }

        return ['payment' => ['mollie' => ['instructions' => $instructions]]];
    }

    /**
     * @return array<string, array{html: string, cssClass: string}>
     */
    private function getInstructionsPerMethod(int $storeId): array
    {
        return array_filter(
            array_combine(
                PaymentInstructions::SUPPORTED_METHODS,
                array_map(
                    fn (string $method): array => [
                        'html' => $this->paymentInstructions->getHtml($method, $storeId),
                        'cssClass' => $this->paymentInstructions->getCssClass($method, $storeId),
                    ],
                    PaymentInstructions::SUPPORTED_METHODS,
                ),
            ),
            fn (array $instructions): bool => $instructions['html'] !== '',
        );
    }
}
