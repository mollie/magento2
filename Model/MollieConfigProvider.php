<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model;

use Mollie\Api\MollieApiClient;
use Mollie\Payment\Config;
use Mollie\Payment\Helper\General as MollieHelper;
use Mollie\Payment\Model\Mollie as MollieModel;
use Mollie\Payment\Service\Mollie\GetIssuers;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Escaper;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\MethodInterface;

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
        'mollie_methods_directdebit',
        'mollie_methods_ideal',
        'mollie_methods_kbc',
        'mollie_methods_mealvoucher',
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
     * @var array|null
     */
    private $methodData;
    /**
     * @var Config
     */
    private $config;
    /**
     * @var Resolver
     */
    private $localeResolver;
    /**
     * @var GetIssuers
     */
    private $getIssuers;

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
     * @param Resolver             $localeResolver
     * @param Config               $config
     * @param GetIssuers           $getIssuers
     */
    public function __construct(
        MollieModel $mollieModel,
        MollieHelper $mollieHelper,
        PaymentHelper $paymentHelper,
        CheckoutSession $checkoutSession,
        AssetRepository $assetRepository,
        ScopeConfigInterface $scopeConfig,
        Escaper $escaper,
        Resolver $localeResolver,
        Config $config,
        GetIssuers $getIssuers
    ) {
        $this->mollieModel = $mollieModel;
        $this->mollieHelper = $mollieHelper;
        $this->paymentHelper = $paymentHelper;
        $this->checkoutSession = $checkoutSession;
        $this->escaper = $escaper;
        $this->assetRepository = $assetRepository;
        $this->scopeConfig = $scopeConfig;
        $this->config = $config;
        $this->localeResolver = $localeResolver;
        $this->getIssuers = $getIssuers;
        foreach ($this->methodCodes as $code) {
            $this->methods[$code] = $this->getMethodInstance($code);
        }
    }

    /**
     * @param $code
     * @return MethodInterface|null
     */
    public function getMethodInstance($code)
    {
        try {
            return $this->paymentHelper->getMethodInstance($code);
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('error', 'Function: getMethodInstance: ' . $e->getMessage());
            return null;
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
        $config['payment']['mollie']['testmode'] = $this->config->isTestMode();
        $config['payment']['mollie']['profile_id'] = $this->config->getProfileId();
        $config['payment']['mollie']['locale'] = $this->localeResolver->getLocale();
        $config['payment']['mollie']['creditcard']['use_components'] = $this->config->creditcardUseComponents();
        $apiKey = $this->mollieHelper->getApiKey();
        $useImage = $this->mollieHelper->useImage();

        try {
            $mollieApi = $this->mollieModel->loadMollieApi($apiKey);
        } catch (\Exception $exception) {
            $mollieApi = null;
            $this->mollieHelper->addTolog('error', $exception->getMessage());
        }

        foreach ($this->methodCodes as $code) {
            if (empty($this->methods[$code])) {
                continue;
            }

            $isAvailable = $this->methods[$code]->isAvailable();
            $config['payment']['instructions'][$code] = $this->getInstructions($code);

            $config['payment']['image'][$code] = '';
            if ($useImage) {
                $cleanCode = str_replace('mollie_methods_', '', $code);
                $url = $this->assetRepository->getUrl('Mollie_Payment::images/methods/' . $cleanCode . '.png');
                $config['payment']['image'][$code] = $url;
            }

            if ($isAvailable &&
                $mollieApi &&
                in_array($code, ['mollie_methods_ideal', 'mollie_methods_kbc', 'mollie_methods_giftcard'])
            ) {
                $config = $this->getIssuers($mollieApi, $code, $config);
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
        if ($this->methodData !== null) {
            return $this->methodData;
        }

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
                $this->methodData[$methodId] = [
                    'image' => $method->image->size2x
                ];
            }
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('error', 'Function: getActiveMethods: ' . $e->getMessage());
            $this->methodData = [];
        }

        return $this->methodData;
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

    /**
     * @param MollieApiClient $mollieApi
     * @param string $code
     * @param array $config
     * @return array
     */
    private function getIssuers(MollieApiClient $mollieApi, $code, array $config)
    {
        $issuerListType = $this->mollieHelper->getIssuerListType($code);
        $config['payment']['issuersListType'][$code] = $issuerListType;
        $config['payment']['issuers'][$code] = $this->getIssuers->execute($mollieApi, $code, $issuerListType);

        return $config;
    }
}
