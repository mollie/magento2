<?php

declare(strict_types=1);

namespace Mollie\Payment\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Mollie\Api\Resources\Terminal;
use Mollie\Payment\Service\Mollie\MollieApiClient;
use Mollie\Payment\Service\Mollie\PointOfSaleAvailability;

class MollieTerminalConfigProvider implements ConfigProviderInterface
{
    /**
     * @var PointOfSaleAvailability
     */
    private $pointOfSaleAvailability;
    /**
     * @var MollieApiClient
     */
    private $mollieApiClient;
    /**
     * @var Session
     */
    private $checkoutSession;

    public function __construct(
        PointOfSaleAvailability $pointOfSaleAvailability,
        MollieApiClient $mollieApiClient,
        Session $checkoutSession
    ) {
        $this->pointOfSaleAvailability = $pointOfSaleAvailability;
        $this->mollieApiClient = $mollieApiClient;
        $this->checkoutSession = $checkoutSession;
    }

    public function getConfig(): array
    {
        $cart = $this->checkoutSession->getQuote();

        if (!$this->pointOfSaleAvailability->isAvailable($cart)) {
            return [];
        }

        $mollieApiClient = $this->mollieApiClient->loadByStore($cart->getStoreId());
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
                ]
            ]
        ];
    }
}
