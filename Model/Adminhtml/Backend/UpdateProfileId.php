<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Adminhtml\Backend;

use Magento\Framework\App\Config\Storage\WriterInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Mollie\MollieApiClient;

class UpdateProfileId
{
    public function __construct(
        private MollieApiClient $mollieApiClient,
        private WriterInterface $configWriter
    ) {}

    public function execute(string $apiKey, string $scope, int $scopeId): void
    {
        $client = $this->mollieApiClient->loadByApiKey($apiKey);
        $profile = $client->profiles->get('me');
        $profileId = $profile->id;

        $this->configWriter->save(
            Config::GENERAL_PROFILEID,
            $profileId,
            $scope,
            $scopeId,
        );
    }
}
