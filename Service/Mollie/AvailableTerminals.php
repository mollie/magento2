<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie;

use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\Resources\Terminal;

class AvailableTerminals
{
    /**
     * @var MollieApiClient
     */
    private $mollieApiClient;

    public function __construct(
        MollieApiClient $mollieApiClient
    ) {
        $this->mollieApiClient = $mollieApiClient;
    }

    /**
     * @return array{
     *      id: string,
     *      brand: string,
     *      model: string,
     *      serialNumber: string|null,
     *      description: string
     *  }
     */
    public function execute(?int $storeId = null): array
    {
        try {
            $mollieApiClient = $this->mollieApiClient->loadByStore($storeId);
            $terminals = $mollieApiClient->terminals->page();
        } catch (ApiException $exception) {
            return [];
        }

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

        return $output;
    }
}
