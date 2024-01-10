<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Payment\Model\Mollie as MollieModel;

/**
 * Class Tests
 *
 * @package Mollie\Payment\Helper
 */
class Tests extends AbstractHelper
{
    /**
     * @var MollieModel
     */
    private $mollieModel;

    public function __construct(
        Context $context,
        MollieModel $mollieModel
    ) {
        $this->mollieModel = $mollieModel;
        parent::__construct($context);
    }

    /**
     * @param string|null $testKey
     * @param string|null $liveKey
     *
     * @return array
     */
    public function getMethods($testKey = null, $liveKey = null)
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
                    $mollieApi = $this->mollieModel->loadMollieApi($testKey);
                    $methods = $mollieApi->methods->allAvailable() ?? [];

                    foreach ($methods as $apiMethod) {
                        $availableMethods[] = ucfirst($apiMethod->id);
                    }

                    try {
                        $mollieApi->terminals->page();
                        $availableMethods[] = 'Point of sale';
                    } catch (ApiException $exception) {}

                    sort($availableMethods);

                    if (empty($availableMethods)) {
                        $msg = __('Enabled Methods: None, Please enable the payment methods in your Mollie dashboard.');
                        $methodsMsg = '<span class="enabled-methods-error">' . $msg . '</span>';
                    } else {
                        $msg = __('Enabled Methods') . ': ' . implode(', ', $availableMethods);
                        $methodsMsg = '<span class="enabled-methods">' . $msg . '</span>';
                    }

                    $results[] = '<span class="mollie-success">' . __('Test API-key: Success!') . $methodsMsg . '</span>';
                } catch (\Exception $e) {
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
                    $mollieApi = $this->mollieModel->loadMollieApi($liveKey);
                    $methods = $mollieApi->methods->allAvailable() ?? [];

                    foreach ($methods as $apiMethod) {
                        $availableMethods[] = ucfirst($apiMethod->id);
                    }

                    try {
                        $mollieApi->terminals->page();
                        $availableMethods[] = 'Point of sale';
                    } catch (ApiException $exception) {}

                    sort($availableMethods);

                    if (empty($availableMethods)) {
                        $msg = __('Enabled Methods: None, Please enable the payment methods in your Mollie dashboard.');
                        $methodsMsg = '<span class="enabled-methods-error">' . $msg . '</span>';
                    } else {
                        $msg = __('Enabled Methods: %1', implode(', ', $availableMethods));
                        $methodsMsg = '<span class="enabled-methods">' . $msg . '</span>';
                    }

                    $results[] = '<span class="mollie-success">' . __('Live API-key: Success!') . $methodsMsg . '</span>';
                } catch (\Exception $e) {
                    $results[] = '<span class="mollie-error">' . __('Live API-key: %1', $e->getMessage()) . '</span>';
                }
            }
        }

        return $results;
    }
}
