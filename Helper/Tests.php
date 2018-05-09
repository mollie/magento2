<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Mollie\Payment\Model\Mollie as MollieModel;
use Mollie\Api\CompatibilityChecker;

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
    /**
     * @var CompatibilityChecker
     */
    private $compatibilityChecker;

    /**
     * Tests constructor.
     *
     * @param Context              $context
     * @param MollieModel          $mollieModel
     * @param CompatibilityChecker $compatibilityChecker
     */
    public function __construct(
        Context $context,
        MollieModel $mollieModel,
        CompatibilityChecker $compatibilityChecker
    ) {
        $this->mollieModel = $mollieModel;
        $this->compatibilityChecker = $compatibilityChecker;
        parent::__construct($context);
    }

    /**
     * @param null $testKey
     * @param null $liveKey
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
                    $methods = $mollieApi->methods->all();

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
                    $methods = $mollieApi->methods->all();
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
        $compatibilityChecker = $this->compatibilityChecker;

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

        return $results;
    }
}