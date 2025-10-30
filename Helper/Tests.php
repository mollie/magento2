<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Helper;

use Exception;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Payment\Service\Mollie\MollieApiClient;

/**
 * Class Tests
 *
 * @package Mollie\Payment\Helper
 */
class Tests extends AbstractHelper
{
    public function __construct(
        Context $context,
        private MollieApiClient $mollieApiClient,
    ) {
        parent::__construct($context);
    }

    /**
     * @param string|null $testKey
     * @param string|null $liveKey
     *
     * @return array
     */
    public function getMethods(?string $testKey = null, ?string $liveKey = null): array
    {
        $results = [];

        if (empty($testKey)) {
            $results[] = '<span class="mollie-error">' . __('Test API-key: Empty value') . '</span>';
        } else {
            if (!preg_match('/^test_\w+$/', $testKey)) {
                $results[] = '<span class="mollie-error">' . __('Test API-key: Should start with "test_"') . '</span>';
            } else {
                try {
                    $availableMethods = [];
                    $mollieApi = $this->mollieApiClient->loadByApiKey($testKey);
                    $methods = $mollieApi->methods->allEnabled() ?? [];

                    foreach ($methods as $apiMethod) {
                        $availableMethods[] = ucfirst($apiMethod->id);
                    }

                    try {
                        $mollieApi->terminals->page();
                        $availableMethods[] = 'Point of sale';
                    } catch (ApiException $exception) {
                    }

                    sort($availableMethods);

                    if (empty($availableMethods)) {
                        $msg = __('Enabled Methods: None, Please enable the payment methods in your Mollie dashboard.');
                        $methodsMsg = '<span class="enabled-methods-error">' . $msg . '</span>';
                    } else {
                        $msg = __('Enabled Methods') . ': ' . implode(', ', $availableMethods);
                        $methodsMsg = '<span class="enabled-methods">' . $msg . '</span>';
                    }

                    $results[] = '<span class="mollie-success">' . __('Test API-key: Success!') . $methodsMsg . '</span>';
                } catch (Exception $e) {
                    $results[] = '<span class="mollie-error">' . __('Test API-key: %1', $e->getMessage()) . '</span>';
                }
            }
        }

        if (empty($liveKey)) {
            $results[] = '<span class="mollie-error">' . __('Live API-key: Empty value') . '</span>';
        } else {
            if (!preg_match('/^live_\w+$/', $liveKey)) {
                $results[] = '<span class="mollie-error">' . __('Live API-key: Should start with "live_"') . '</span>';
            } else {
                try {
                    $availableMethods = [];
                    $mollieApi = $this->mollieApiClient->loadByApiKey($liveKey);
                    $methods = $mollieApi->methods->allEnabled() ?? [];

                    foreach ($methods as $apiMethod) {
                        $availableMethods[] = ucfirst($apiMethod->id);
                    }

                    try {
                        $mollieApi->terminals->page();
                        $availableMethods[] = 'Point of sale';
                    } catch (ApiException $exception) {
                    }

                    sort($availableMethods);

                    if (empty($availableMethods)) {
                        $msg = __('Enabled Methods: None, Please enable the payment methods in your Mollie dashboard.');
                        $methodsMsg = '<span class="enabled-methods-error">' . $msg . '</span>';
                    } else {
                        $msg = __('Enabled Methods: %1', implode(', ', $availableMethods));
                        $methodsMsg = '<span class="enabled-methods">' . $msg . '</span>';
                    }

                    $results[] = '<span class="mollie-success">' . __('Live API-key: Success!') . $methodsMsg . '</span>';
                } catch (Exception $e) {
                    $results[] = '<span class="mollie-error">' . __('Live API-key: %1', $e->getMessage()) . '</span>';
                }
            }
        }

        return $results;
    }
}
