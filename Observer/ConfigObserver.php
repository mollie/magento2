<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Observer;

use Exception;
use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Message\ManagerInterface;
use Mollie\Api\Resources\Method;
use Mollie\Payment\Config;
use Mollie\Payment\Helper\General as MollieHelper;
use Mollie\Payment\Model\Methods\Directdebit;
use Mollie\Payment\Model\Methods\GooglePay;
use Mollie\Payment\Model\Methods\Pointofsale;
use Mollie\Payment\Model\Mollie as MollieModel;
use Mollie\Payment\Service\Mollie\MollieApiClient;

/**
 * Class ConfigObserver
 *
 * @package Mollie\Payment\Observer
 */
class ConfigObserver implements ObserverInterface
{
    public function __construct(
        private ManagerInterface $messageManager,
        private MollieModel $mollieModel,
        private MollieHelper $mollieHelper,
        private MollieApiClient $mollieApiClient,
        private Config $config
    ) {}

    /**
     * @param EventObserver $observer
     */
    public function execute(EventObserver $observer): void
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
    public function validatePaymentMethods(?int $storeId, string $modus): void
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
            $apiMethods = $this->mollieApiClient->loadByStore($storeId)->methods->allEnabled([
                'includeWallets' => ['applepay'],
            ]);
        } catch (Exception $e) {
            $this->mollieHelper->addTolog('error', $e->getMessage());
            $this->messageManager->addErrorMessage($e->getMessage());

            return;
        }

        if ($apiMethods->count() === 0) {
            return;
        }

        $activeMethods = $this->mollieHelper->getAllActiveMethods($storeId);

        $methods = [];
        $apiMethods = array_filter((array) $apiMethods, function (Method $method): bool {
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
                ],
            );
        }
    }
}
