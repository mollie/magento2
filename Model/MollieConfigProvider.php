<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Api\MollieApiClient;
use Mollie\Payment\Config;
use Mollie\Payment\Helper\General as MollieHelper;
use Mollie\Payment\Model\Mollie as MollieModel;
use Mollie\Payment\Service\Mollie\ApplePay\SupportedNetworks;
use Mollie\Payment\Service\Mollie\GetIssuers;
use Mollie\Payment\Service\Mollie\MethodParameters;

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
        'mollie_methods_applepay',
        'mollie_methods_bancontact',
        'mollie_methods_banktransfer',
        'mollie_methods_belfius',
        'mollie_methods_billie',
        'mollie_methods_creditcard',
        'mollie_methods_directdebit',
        'mollie_methods_eps',
        'mollie_methods_giftcard',
        'mollie_methods_giropay',
        'mollie_methods_ideal',
        'mollie_methods_in3',
        'mollie_methods_kbc',
        'mollie_methods_klarna',
        'mollie_methods_klarnapaylater',
        'mollie_methods_klarnapaynow',
        'mollie_methods_klarnasliceit',
        'mollie_methods_mybank',
        'mollie_methods_paypal',
        'mollie_methods_paysafecard',
        'mollie_methods_pointofsale',
        'mollie_methods_przelewy24',
        'mollie_methods_sofort',
        'mollie_methods_voucher',
    ];
    /**
     * @var array
     */
    private $methods = [];
    /**
     * @var AssetRepository
     */
    private $assetRepository;
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
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var MethodParameters
     */
    private $methodParameters;
    /**
     * @var SupportedNetworks
     */
    private $supportedNetworks;

    public function __construct(
        MollieModel $mollieModel,
        MollieHelper $mollieHelper,
        PaymentHelper $paymentHelper,
        CheckoutSession $checkoutSession,
        AssetRepository $assetRepository,
        Resolver $localeResolver,
        Config $config,
        GetIssuers $getIssuers,
        StoreManagerInterface $storeManager,
        MethodParameters $methodParameters,
        SupportedNetworks $supportedNetworks
    ) {
        $this->mollieModel = $mollieModel;
        $this->mollieHelper = $mollieHelper;
        $this->paymentHelper = $paymentHelper;
        $this->checkoutSession = $checkoutSession;
        $this->assetRepository = $assetRepository;
        $this->config = $config;
        $this->localeResolver = $localeResolver;
        $this->getIssuers = $getIssuers;
        $this->storeManager = $storeManager;
        $this->methodParameters = $methodParameters;
        $this->supportedNetworks = $supportedNetworks;

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
    public function getConfig(): array
    {
        $store = $this->storeManager->getStore();
        $storeId = $store->getId();
        $storeName = $store->getFrontendName();

        $config = [];
        $config['payment']['mollie']['testmode'] = $this->config->isTestMode($storeId);
        $config['payment']['mollie']['profile_id'] = $this->config->getProfileId($storeId);
        $config['payment']['mollie']['locale'] = $this->getLocale($storeId);
        $config['payment']['mollie']['creditcard']['use_components'] = $this->config->creditcardUseComponents($storeId);
        $config['payment']['mollie']['applepay']['integration_type'] = $this->config->applePayIntegrationType($storeId);
        $config['payment']['mollie']['applepay']['supported_networks'] = $this->supportedNetworks->execute((int)$storeId);
        $config['payment']['mollie']['store']['name'] = $storeName;
        $config['payment']['mollie']['store']['currency'] = $this->config->getStoreCurrency($storeId);
        $config['payment']['mollie']['vault']['enabled'] = $this->config->isMagentoVaultEnabled($storeId);
        $apiKey = $this->mollieHelper->getApiKey();
        $useImage = $this->mollieHelper->useImage();

        $activeMethods = [];
        try {
            $mollieApi = $this->mollieModel->loadMollieApi($apiKey);
            $activeMethods = $this->getActiveMethods($mollieApi);
        } catch (\Exception $exception) {
            $mollieApi = null;
            $this->mollieHelper->addTolog('error', $exception->getMessage());
        }

        foreach ($this->methodCodes as $code) {
            if (empty($this->methods[$code])) {
                continue;
            }

            $isAvailable = $this->methods[$code]->isActive();

            $config['payment']['image'][$code] = '';
            if ($useImage) {
                $cleanCode = str_replace('mollie_methods_', '', $code);
                $url = $this->assetRepository->getUrl('Mollie_Payment::images/methods/' . $cleanCode . '.svg');
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
     * @param MollieApiClient $mollieApi
     * @param CartInterface|null $cart
     *
     * @return array
     */
    public function getActiveMethods(MollieApiClient $mollieApi, CartInterface $cart = null): array
    {
        if (!$cart) {
            $cart = $this->checkoutSession->getQuote();
        }

        if ($this->methodData !== null) {
            return $this->methodData;
        }

        try {
            $amount = $this->mollieHelper->getOrderAmountByQuote($cart);
            $parameters = [
                'amount[value]' => $amount['value'],
                'amount[currency]' => $amount['currency'],
                'resource' => 'orders',
                'includeWallets' => 'applepay',
            ];

            $this->methodData = [];
            $apiMethods = $mollieApi->methods->allActive($this->methodParameters->enhance($parameters, $cart));
            foreach ($apiMethods as $method) {
                $methodId = 'mollie_methods_' . $method->id;
                $this->methodData[$methodId] = [
                    'image' => $method->image->size2x
                ];
            }
        } catch (\Exception $e) {
            $this->mollieHelper->addTolog('info', 'Function: getActiveMethods: ' . $e->getMessage());
            $this->methodData = [];
        }

        return $this->methodData;
    }

    /**
     * @param MollieApiClient $mollieApi
     * @param string $code
     * @param array $config
     * @return array
     */
    private function getIssuers(MollieApiClient $mollieApi, string $code, array $config): array
    {
        $issuerListType = $this->mollieHelper->getIssuerListType($code);
        $config['payment']['issuersListType'][$code] = $issuerListType;
        $config['payment']['issuers'][$code] = $this->getIssuers->execute($mollieApi, $code, $issuerListType);

        return $config;
    }

    /**
     * @param int $storeId
     * @return string
     */
    private function getLocale($storeId)
    {
        $locale = $this->config->getLocale($storeId);

        // Empty == autodetect, so use the store.
        if (!$locale || $locale == 'store') {
            return $this->localeResolver->getLocale();
        }

        return $locale;
    }
}
