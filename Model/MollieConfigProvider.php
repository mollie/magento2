<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model;

use Exception;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\View\Asset\Repository;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\MethodInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\StoreManagerInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Helper\General;
use Mollie\Payment\Service\Mollie\ApplePay\SupportedNetworks;
use Mollie\Payment\Service\Mollie\GetIssuers;
use Mollie\Payment\Service\Mollie\MethodParameters;
use Mollie\Payment\Service\Mollie\MollieApiClient;
use Mollie\Payment\Service\Mollie\PaymentMethods;

/**
 * Class MollieConfigProvider
 *
 * @package Mollie\Payment\Model
 */
class MollieConfigProvider implements ConfigProviderInterface
{
    private array $methods = [];
    private ?array $methodData = null;

    public function __construct(
        private Http $request,
        private General $mollieHelper,
        private Data $paymentHelper,
        private Session $checkoutSession,
        private Repository $assetRepository,
        private Resolver $localeResolver,
        private Config $config,
        private GetIssuers $getIssuers,
        private StoreManagerInterface $storeManager,
        private MethodParameters $methodParameters,
        private SupportedNetworks $supportedNetworks,
        private MollieApiClient $mollieApiClient,
    ) {
        foreach (PaymentMethods::METHODS as $code) {
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
        } catch (Exception $e) {
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
        // Do not load the config when on the cart page.
        if (!$this->config->isModuleEnabled() || $this->request->getControllerName() === 'cart') {
            return [];
        }

        $store = $this->storeManager->getStore();
        $storeId = (int) $store->getId();
        $storeName = $store->getFrontendName();

        $config = [];
        $config['payment']['mollie']['testmode'] = $this->config->isTestMode($storeId);
        $config['payment']['mollie']['profile_id'] = $this->config->getProfileId($storeId);
        $config['payment']['mollie']['locale'] = $this->getLocale($storeId);
        $config['payment']['mollie']['creditcard']['use_components'] = $this->config->creditcardUseComponents($storeId);
        $config['payment']['mollie']['applepay']['integration_type'] = $this->config->applePayIntegrationType($storeId);
        $config['payment']['mollie']['applepay']['supported_networks'] = $this->supportedNetworks->execute((int) $storeId);
        $config['payment']['mollie']['store']['name'] = $storeName;
        $config['payment']['mollie']['store']['currency'] = $this->config->getStoreCurrency($storeId);
        $config['payment']['mollie']['vault']['enabled'] = $this->config->isMagentoVaultEnabled($storeId);
        $useImage = $this->mollieHelper->useImage();

        foreach (PaymentMethods::METHODS as $code) {
            if (empty($this->methods[$code])) {
                continue;
            }

            $config['payment']['image'][$code] = '';
            if ($useImage) {
                $cleanCode = str_replace('mollie_methods_', '', $code);
                $url = $this->assetRepository->getUrl('Mollie_Payment::images/methods/' . $cleanCode . '.svg');
                $config['payment']['image'][$code] = $url;
            }

            if (
                in_array($code, ['mollie_methods_kbc', 'mollie_methods_giftcard']) &&
                $this->methods[$code]->isActive() &&
                $this->isMethodActive($code)
            ) {
                $config = $this->getIssuers($code, $config);
            }
        }

        return $config;
    }

    public function getActiveMethods(?CartInterface $cart = null): array
    {
        if ($this->methodData !== null) {
            return $this->methodData;
        }

        if (!$cart) {
            $cart = $this->checkoutSession->getQuote();
        }

        $mollieApi = $this->mollieApiClient->loadByStore();
        try {
            $amount = $this->mollieHelper->getOrderAmountByQuote($cart);
            $parameters = [
                'amount[value]' => $amount['value'],
                'amount[currency]' => $amount['currency'],
                'resource' => 'orders',
                'includeWallets' => ['applepay'],
                'billingCountry' => $cart->getBillingAddress()->getCountry(),
            ];

            $this->methodData = [];
            $apiMethods = $mollieApi->methods->allEnabled($this->methodParameters->enhance($parameters, $cart));
            foreach ($apiMethods as $method) {
                $methodId = 'mollie_methods_' . $method->id;
                $this->methodData[$methodId] = [
                    'image' => $method->image->size2x,
                ];
            }
        } catch (Exception $e) {
            $this->mollieHelper->addTolog('info', 'Function: getActiveMethods: ' . $e->getMessage());
            $this->methodData = [];
        }

        return $this->methodData;
    }

    private function getIssuers(string $code, array $config): array
    {
        $issuerListType = $this->config->getIssuerListType(
            $code,
            storeId($this->storeManager->getStore()->getId()),
        );

        $config['payment']['issuersListType'][$code] = $issuerListType;

        try {
            $config['payment']['issuers'][$code] = $this->getIssuers->execute($code, $issuerListType);
        } catch (Exception $exception) {
            $this->config->addTolog('error', 'Unable to load issuers: ' . $exception->getMessage());
            $config['payment']['issuers'][$code] = [];
        }

        return $config;
    }

    /**
     * @param int $storeId
     * @return string
     */
    private function getLocale(?int $storeId)
    {
        $locale = $this->config->getLocale($storeId);

        // Empty == autodetect, so use the store.
        if (!$locale || $locale == 'store') {
            return $this->localeResolver->getLocale();
        }

        return $locale;
    }

    private function isMethodActive(string $code): bool
    {
        if ($this->config->isMethodsApiEnabled()) {
            return true;
        }

        return array_key_exists($code, $this->getActiveMethods());
    }
}
