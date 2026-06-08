<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Mollie\Api\Resources\Terminal;
use Mollie\Payment\Service\Mollie\MollieApiClient;
use Mollie\Payment\Service\Mollie\PointOfSaleAvailability;

class MollieTerminalConfigProvider implements ConfigProviderInterface
{
    public function __construct(
        private PointOfSaleAvailability $pointOfSaleAvailability,
        private MollieApiClient $mollieApiClient,
        private Session $checkoutSession
    ) {}

    public function getConfig(): array
    {
        $cart = $this->checkoutSession->getQuote();

        if (!$this->pointOfSaleAvailability->isAvailable($cart)) {
            return [];
        }

        $mollieApiClient = $this->mollieApiClient->loadByStore(storeId($cart->getStoreId()));
        $terminals = $mollieApiClient->terminals->page();

        $output = [];
        /** @var Terminal $terminal */
        foreach ($terminals as $terminal) {
            if (!$terminal->isActive()) {
                continue;
            }

            $output[] = [
                'id' => $terminal->id,
                'brand' => $terminal->brand,
                'model' => $terminal->model,
                'serialNumber' => $terminal->serialNumber,
                'description' => $terminal->description,
            ];
        }

        return [
            'payment' => [
                'mollie' => [
                    'terminals' => $output,
                ],
            ],
        ];
    }
}
