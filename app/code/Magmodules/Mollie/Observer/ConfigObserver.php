<?php
/**
 * Copyright © 2016 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Mollie\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magmodules\Mollie\Model\Mollie as MollieModel;
use Magmodules\Mollie\Helper\General as MollieHelper;

class ConfigObserver implements ObserverInterface
{

    protected $messageManager;
    protected $mollieModel;
    protected $mollieHelper;

    /**
     * ConfigObserver constructor.
     *
     * @param ManagerInterface $messageManager
     * @param MollieModel      $mollieModel
     * @param MollieHelper     $mollieHelper
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
            $this->mollieHelper->addTolog('error', $e->getMessage());
            throw new LocalizedException(__('Mollie: %1', $e->getMessage()));
        }

        if (empty($apiMethods)) {
            return;
        }

        $activeMethods = $this->mollieHelper->getAllActiveMethods($storeId);

        $methods = [];
        foreach ($apiMethods as $apiMethod) {
            $methods[$apiMethod->id] = [
                'max' => $apiMethod->amount->maximum
            ];
        }

        $errors = [];
        foreach ($activeMethods as $k => $v) {
            $code = $v['code'];
            if (!isset($methods[$code])) {
                $errors[] = __('%1: method not enabled in Mollie Dashboard', ucfirst($code));
                continue;
            }
            if ($v['max'] > $methods[$code]['max']) {
                $errors[] = __(
                    '%1: maximum is set higher than set in Mollie dashboard: %2, please correct.',
                    ucfirst($code),
                    '€ ' . number_format($methods[$code]['max'], 2, ',', '.')
                );
            }
        }

        if (!empty($errors)) {
            $errorMethods = implode('<br/>', $errors);
            $this->messageManager->addError($errorMethods);
        }
    }
}
