<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model;

use Mollie\Payment\Model\Mollie as MollieModel;
use Mollie\Payment\Helper\General as MollieHelper;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class MollieConfigProvider
 *
 * @package Mollie\Payment\Model
 */
class MollieConfigProvider implements ConfigProviderInterface
{

    /**
     * @var array
     */
    private $methodCodes = [
        'mollie_methods_bancontact',
        'mollie_methods_banktransfer',
        'mollie_methods_belfius',
        'mollie_methods_creditcard',
        'mollie_methods_ideal',
        'mollie_methods_kbc',
        'mollie_methods_paypal',
        'mollie_methods_paysafecard',
        'mollie_methods_sofort',
        'mollie_methods_inghomepay',
        'mollie_methods_giropay',
        'mollie_methods_eps',
        'mollie_methods_klarnapaylater',
        'mollie_methods_klarnasliceit',
        'mollie_methods_giftcard',
        'mollie_methods_przelewy24',
        'mollie_methods_applepay',
        'mollie_methods_mybank',
    ];
    /**
     * @var array
     */
    private $methods = [];
    /**
     * @var Escaper
     */
    private $escaper;
    /**
     * @var AssetRepository
     */
    private $assetRepository;
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;
    /**
     * @var Mollie
     */
    private $mollieModel;
    /**
     * @var MollieHelper
     */
    private $mollieHelper;
    /**
     * @var PaymentHelper
     */
    private $paymentHelper;
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * MollieConfigProvider constructor.
     *
     * @param Mollie               $mollieModel
     * @param MollieHelper         $mollieHelper
     * @param PaymentHelper        $paymentHelper
     * @param CheckoutSession      $checkoutSession
     * @param AssetRepository      $assetRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param Escaper              $escaper
     */
    public function __construct(
        MollieModel $mollieModel,
        MollieHelper $mollieHelper,
        PaymentHelper $paymentHelper,
        CheckoutSession $checkoutSession,
        AssetRepository $assetRepository,
        ScopeConfigInterface $scopeConfig,
        Escaper $escaper
    ) {
        $this->mollieModel = $mollieModel;
        $this->mollieHelper = $mollieHelper;
        $this->paymentHelper = $paymentHelper;
        $this->checkoutSession = $checkoutSession;
        $this->escaper = $escaper;
        $this->assetRepository = $assetRepository;
        $this->scopeConfig = $scopeConfig;
        foreach ($this->methodCodes as $code) {
            $this->methods[$code] = $this->getMethodInstance($code);
        }
    }

    /**
     * @param $code
     *
     * @return \Magento\Payment\Model\MethodInterface
     */
    public function getMethodInstance($code)
    {
        try {
            return $this->paymentHelper->getMethodInstance($code);
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('error', 'Function: getMethodInstance: ' . $e->getMessage());
        }
    }

    /**
     * Config Data for checkout
     *
     * @return array
     */
    public function getConfig()
    {
        $config = [];
        $apiKey = $this->mollieHelper->getApiKey();
        $useImage = $this->mollieHelper->useImage();

        try {
            $mollieApi = $this->mollieModel->loadMollieApi($apiKey);
            $activeMethods = $this->getActiveMethods($mollieApi);
        } catch (\Exception $e) {
            $mollieApi = '';
            $this->mollieHelper->addTolog('error', $e->getMessage());
            $activeMethods = [];
        }

        foreach ($this->methodCodes as $code) {
            if (!empty($this->methods[$code]) && $this->methods[$code]->isAvailable()) {
                if (!empty($activeMethods[$code])) {
                    $config['payment']['isActive'][$code] = true;
                    $config['payment']['instructions'][$code] = $this->getInstructions($code);
                    if ($useImage && isset($activeMethods[$code]['image'])) {
                        $config['payment']['image'][$code] = $activeMethods[$code]['image'];
                    } else {
                        $config['payment']['image'][$code] = '';
                    }
                    if ($code == 'mollie_methods_ideal') {
                        $issuerListType = $this->mollieHelper->getIssuerListType($code);
                        $config['payment']['issuersListType'][$code] = $issuerListType;
                        $config['payment']['issuers'][$code] = $this->mollieModel->getIssuers(
                            $mollieApi,
                            $code,
                            $issuerListType
                        );
                    }
                    if ($code == 'mollie_methods_kbc') {
                        $issuerListType = $this->mollieHelper->getIssuerListType($code);
                        $config['payment']['issuersListType'][$code] = $issuerListType;
                        $config['payment']['issuers'][$code] = $this->mollieModel->getIssuers(
                            $mollieApi,
                            $code,
                            $issuerListType
                        );
                    }
                    if ($code == 'mollie_methods_giftcard') {
                        $issuerListType = $this->mollieHelper->getIssuerListType($code);
                        $config['payment']['issuersListType'][$code] = $issuerListType;
                        $config['payment']['issuers'][$code] = $this->mollieModel->getIssuers(
                            $mollieApi,
                            $code,
                            $issuerListType
                        );
                        if (empty($config['payment']['issuers'][$code])) {
                            $config['payment']['isActive'][$code] = false;
                        }
                    }
                } else {
                    $config['payment']['isActive'][$code] = false;
                }
            } else {
                $config['payment']['isActive'][$code] = false;
            }
        }

        return $config;
    }

    /**
     * @param \Mollie\Api\MollieApiClient $mollieApi
     *
     * @return array
     */
    public function getActiveMethods($mollieApi)
    {
        $methodData = [];

        try {
            $quote = $this->checkoutSession->getQuote();
            $amount = $this->mollieHelper->getOrderAmountByQuote($quote);
            $params = [
                'amount[value]' => $amount['value'],
                'amount[currency]' => $amount['currency'],
                'resource' => 'orders',
                'includeWallets' => 'applepay',
            ];
            $apiMethods = $mollieApi->methods->all($params);

            foreach ($apiMethods as $method) {
                $methodId = 'mollie_methods_' . $method->id;
                $methodData[$methodId] = [
                    'image' => $method->image->size2x
                ];
            }
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('error', 'Function: getActiveMethods: ' . $e->getMessage());
        }

        return $methodData;
    }

    /**
     * Instruction data
     *
     * @param $code
     *
     * @return string
     */
    protected function getInstructions($code)
    {
        return nl2br($this->escaper->escapeHtml($this->methods[$code]->getInstructions()));
    }
}
