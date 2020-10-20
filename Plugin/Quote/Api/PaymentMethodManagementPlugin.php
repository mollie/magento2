<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 *  See COPYING.txt for license details.
 */

namespace Mollie\Payment\Plugin\Quote\Api;

use Magento\Quote\Api\PaymentMethodManagementInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Model\MollieConfigProvider;

class PaymentMethodManagementPlugin
{
    /**
     * @var Config
     */
    private $config;

    /**
     * @var Mollie
     */
    private $mollieModel;

    /**
     * @var MollieConfigProvider
     */
    private $mollieConfigProvider;

    public function __construct(
        Config $config,
        Mollie $mollieModel,
        MollieConfigProvider $mollieConfigProvider
    ) {
        $this->config = $config;
        $this->mollieModel = $mollieModel;
        $this->mollieConfigProvider = $mollieConfigProvider;
    }

    public function afterGetList(PaymentMethodManagementInterface $subject, $result)
    {
        if (!$this->containsMollieMethods($result)) {
            return $result;
        }

        $apiKey = $this->config->getApiKey();
        $mollieApi = $this->mollieModel->loadMollieApi($apiKey);
        $activeMethods = $this->mollieConfigProvider->getActiveMethods($mollieApi);

        return array_filter($result, function ($method) use ($activeMethods) {
            if (!$method instanceof Mollie) {
                return true;
            }

            return array_key_exists($method->getCode(), $activeMethods);
        });
    }

    private function containsMollieMethods(array $list): bool
    {
        $list = array_filter($list, function ($method) {
            return $method instanceof Mollie;
        });

        return count($list);
    }
}