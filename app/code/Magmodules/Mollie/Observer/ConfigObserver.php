<?php
/**
 * Copyright © 2016 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Mollie\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magmodules\Mollie\Model\Mollie as MollieModel;
use Magmodules\Mollie\Helper\General as MollieHelper;

class ConfigObserver implements ObserverInterface
{

    protected $mollieModel;
    protected $mollieHelper;

    /**
     * ConfigObserver constructor.
     *
     * @param MollieModel  $mollieModel
     * @param MollieHelper $mollieHelper
     */
    public function __construct(
        MollieModel $mollieModel,
        MollieHelper $mollieHelper
    ) {
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
     * @param $storeId
     * @param $modus
     *
     * @return bool|void
     */
    public function validatePaymentMethods($storeId, $modus)
    {
        if ($modus == 'test') {
            return;
        }

        try {
            $apiMethods = $this->mollieModel->getPaymentMethods($storeId, $modus);
        } catch (\Exception $e) {
            throw new \Magento\Framework\Exception\LocalizedException(
                __('Mollie: %1', $e->getMessage())
            );
        }

        if (empty($apiMethods)) {
            return;
        }

        $activeMethods = $this->mollieHelper->getAllActiveMethods($storeId);

        $methods = [];
        foreach ($apiMethods as $apiMethod) {
            $methods[] = $apiMethod->id;
        }

        $errors = [];
        foreach ($activeMethods as $k => $v) {
            if (!in_array($v, $methods)) {
                $errors[] = ucfirst($v);
            }
        }

        if (!empty($errors)) {
            $errorMethods = implode(', ', $errors);
            throw new \Magento\Framework\Exception\LocalizedException(
                __('%1 methods not enabled in Mollie Dashboard', $errorMethods)
            );
        }
    }
}
