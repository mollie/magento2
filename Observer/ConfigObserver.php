<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Message\ManagerInterface;
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
     * ConfigObserver constructor.
     *
     * @param ManagerInterface       $messageManager
     * @param MollieModel            $mollieModel
     * @param MollieHelper           $mollieHelper
     */
    public function __construct(
        ManagerInterface $messageManager,
        MollieModel $mollieModel,
        MollieHelper $mollieHelper
    ) {
        $this->messageManager = $messageManager;
        $this->mollieModel = $mollieModel;
        $this->mollieHelper = $mollieHelper;
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
     * @return mixed
     */
    public function validatePaymentMethods($storeId, $modus)
    {
        if (!class_exists('Mollie\Api\CompatibilityChecker')) {
            $error = $this->mollieHelper->getPhpApiErrorMessage(false);
            $this->mollieHelper->disableExtension();
            $this->mollieHelper->addTolog('error', $error);
            $this->messageManager->addErrorMessage($error);
            return false;
        }

        if ($modus == 'test') {
            return false;
        }

        try {
            $apiMethods = $this->mollieModel->getPaymentMethods($storeId);
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());
            return false;
        }

        if (empty($apiMethods)) {
            return false;
        }

        $activeMethods = $this->mollieHelper->getAllActiveMethods($storeId);

        $methods = [];
        foreach ($apiMethods as $apiMethod) {
            $methods[$apiMethod->id] = $apiMethod;
        }

        $errors = [];
        foreach ($activeMethods as $k => $v) {
            $code = $v['code'];
            if (!isset($methods[$code])) {
                $errors[] = __('%1: method not enabled in Mollie Dashboard', ucfirst($code));
                continue;
            }
        }

        if (!empty($errors)) {
            $errorMethods = implode(', ', $errors);
            $this->messageManager->addErrorMessage($errorMethods);
        }
    }
}
