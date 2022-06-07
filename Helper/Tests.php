<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\ObjectManagerInterface;
use Mollie\Payment\Model\Mollie as MollieModel;
use Mollie\Payment\Service\Mollie\Compatibility\CompatibilityTestInterface;

/**
 * Class Tests
 *
 * @package Mollie\Payment\Helper
 */
class Tests extends AbstractHelper
{
    const XML_PATH_BANKTRANSFER_ACTIVE = 'payment/mollie_methods_banktransfer/active';
    const XML_PATH_BANKTRANSFER_STATUS_PENDING = 'payment/mollie_methods_banktransfer/order_status_pending';

    /**
     * @var ObjectManagerInterface
     */
    private $objectManager;
    /**
     * @var MollieModel
     */
    private $mollieModel;
    /**
     * @var CompatibilityTestInterface[]
     */
    private $tests;

    /**
     * Tests constructor.
     *
     * @param Context                 $context
     * @param ObjectManagerInterface  $objectManager
     * @param MollieModel             $mollieModel
     * @param array                   $tests
     */
    public function __construct(
        Context $context,
        ObjectManagerInterface $objectManager,
        MollieModel $mollieModel,
        array $tests
    ) {
        $this->objectManager = $objectManager;
        $this->mollieModel = $mollieModel;
        $this->tests = $tests;
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
                    $methods = $mollieApi->methods->all([
                        'resource' => 'orders',
                        'includeWallets' => 'applepay',
                    ]);

                    foreach ($methods as $apiMethod) {
                        $availableMethods[] = ucfirst($apiMethod->id);
                    }

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
                    $methods = $mollieApi->methods->all([
                        'resource' => 'orders',
                        'includeWallets' => 'applepay',
                    ]);

                    foreach ($methods as $apiMethod) {
                        $availableMethods[] = ucfirst($apiMethod->id);
                    }

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

    /**
     * @return array
     */
    public function compatibilityChecker()
    {
        $results = [];
        if (class_exists('Mollie\Api\CompatibilityChecker')) {
            $compatibilityChecker = new \Mollie\Api\CompatibilityChecker();
            if (!$compatibilityChecker->satisfiesPhpVersion()) {
                $minPhpVersion = $compatibilityChecker::MIN_PHP_VERSION;
                $msg = __('Error: The client requires PHP version >= %1, you have %2.', $minPhpVersion, PHP_VERSION);
                $results[] = '<span class="mollie-error">' . $msg . '</span>';
            } else {
                $msg = __('Success: PHP version: %1.', PHP_VERSION);
                $results[] = '<span class="mollie-success">' . $msg . '</span>';
            }

            if (!$compatibilityChecker->satisfiesJsonExtension()) {
                $msg = __('Error: PHP extension JSON is not enabled.') . '<br/>';
                $msg .= __('Please make sure to enable "json" in your PHP configuration.');
                $results[] = '<span class="mollie-error">' . $msg . '</span>';
            } else {
                $msg = __('Success: JSON is enabled.');
                $results[] = '<span class="mollie-success">' . $msg . '</span>';
            }
        } else {
            $msg = __('Error: Mollie CompatibilityChecker not found.') . '<br/>';
            $results[] = '<span class="mollie-error">' . $msg . '</span>';
        }

        $bankTransferActive = $this->scopeConfig->isSetFlag(static::XML_PATH_BANKTRANSFER_ACTIVE);
        $bankTransferStatus = $this->scopeConfig->getValue(static::XML_PATH_BANKTRANSFER_STATUS_PENDING);
        if ($bankTransferActive && $bankTransferStatus == 'pending_payment') {
            $msg = __('Warning: We recommend to use a unique payment status for pending Banktransfer payments');
            $results[] = '<span class="mollie-error">' . $msg . '</span>';
        }

        if (stripos(__DIR__, 'app/code') !== false) {
            $msg = __('Warning: We recommend to install the Mollie extension using Composer, currently it\'s installed in the app/code folder.');
            $results[] = '<span class="mollie-error">' . $msg . '</span>';
        }

        foreach ($this->tests as $test) {
            $results = $test->execute($results);
        }

        return $results;
    }
}
