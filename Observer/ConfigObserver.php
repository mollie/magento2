<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Message\ManagerInterface;
use Mollie\Api\Resources\Method;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Methods\Directdebit;
use Mollie\Payment\Model\Methods\GooglePay;
use Mollie\Payment\Model\Methods\Pointofsale;
use Mollie\Payment\Model\Mollie as MollieModel;
use Mollie\Payment\Helper\General as MollieHelper;

/**
 * Class ConfigObserver
 *
 * @package Mollie\Payment\Observer
 */
class ConfigObserver implements ObserverInterface
{

    /**
     * @var ManagerInterface
     */
    private $messageManager;
    /**
     * @var MollieModel
     */
    private $mollieModel;
    /**
     * @var MollieHelper
     */
    private $mollieHelper;
    /**
     * @var Config
     */
    private $config;

    /**
     * ConfigObserver constructor.
     *
     * @param ManagerInterface       $messageManager
     * @param MollieModel            $mollieModel
     * @param MollieHelper           $mollieHelper
     */
    public function __construct(
        ManagerInterface $messageManager,
        MollieModel $mollieModel,
        MollieHelper $mollieHelper,
        Config $config
    ) {
        $this->messageManager = $messageManager;
        $this->mollieModel = $mollieModel;
        $this->mollieHelper = $mollieHelper;
        $this->config = $config;
    }

    /**
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer)
    {
        $storeId = $observer->getStore();
        if (empty($storeId)) {
            $storeId = 0;
        }

        $enabled = $this->mollieHelper->isAvailable($storeId);
        $modus = $this->mollieHelper->getModus($storeId);
        if ($enabled && $modus) {
            $this->validatePaymentMethods($storeId, $modus);
        }
    }

    /**
     * Validate Magento config values against Mollie API
     *
     * @param $storeId
     * @param $modus
     *
     * @return void
     */
    public function validatePaymentMethods($storeId, string $modus): void
    {
        if (!class_exists('Mollie\Api\CompatibilityChecker')) {
            $error = $this->mollieHelper->getPhpApiErrorMessage(false);
            $this->mollieHelper->disableExtension();
            $this->mollieHelper->addTolog('error', $error);
            $this->messageManager->addErrorMessage($error);
            return;
        }

        if ($modus == 'test') {
            return;
        }

        try {
            $apiMethods = $this->mollieModel->getPaymentMethods($storeId);
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
            return;
        }

        if (empty($apiMethods)) {
            return;
        }

        $activeMethods = $this->mollieHelper->getAllActiveMethods($storeId);

        $methods = [];
        $apiMethods = array_filter((array)$apiMethods, function (Method $method) {
            return $method->status == 'activated';
        });

        foreach ($apiMethods as $apiMethod) {
            $methods[$apiMethod->id] = $apiMethod;
        }

        $disabledMethods = [];
        $doNotCheckMethods = [Pointofsale::CODE, Directdebit::CODE, GooglePay::CODE];
        foreach ($activeMethods as $method) {
            $code = $method['code'];
            if (!in_array('mollie_methods_' . $code, $doNotCheckMethods) && !isset($methods[$code])) {
                $disabledMethods[] = $this->config->getMethodTitle($code);
            }
        }

        if ($disabledMethods) {
            $this->messageManager->addComplexErrorMessage(
                'MollieUnavailableMethodsMessage',
                [
                    'methods' => $disabledMethods,
                ]
            );
        }
    }
}
