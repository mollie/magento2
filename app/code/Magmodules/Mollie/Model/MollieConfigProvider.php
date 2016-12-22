<?php
/**
 * Copyright © 2016 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magmodules\Mollie\Model;

use Magmodules\Mollie\Model\Mollie as MollieModel;
use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;

class MollieConfigProvider implements ConfigProviderInterface
{

    const XML_PATH_IMAGES = 'payment/mollie_general/payment_images';

    protected $methodCodes = [
        'mollie_methods_bancontact',
        'mollie_methods_banktransfer',
        'mollie_methods_belfius',
        'mollie_methods_bitcoin',
        'mollie_methods_creditcard',
        'mollie_methods_ideal',
        'mollie_methods_kbc',
        'mollie_methods_paypal',
        'mollie_methods_paysafecard',
        'mollie_methods_sofort'
    ];

    protected $methods = [];
    protected $escaper;
    protected $assetRepository;
    protected $scopeConfig;
    protected $storeManager;
    protected $mollieModel;

    /**
     * MollieConfigProvider constructor.
     * @param PaymentHelper $paymentHelper
     * @param AssetRepository $assetRepository
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param Escaper $escaper
     */
    public function __construct(
        MollieModel $mollieModel,
        PaymentHelper $paymentHelper,
        AssetRepository $assetRepository,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        Escaper $escaper
    ) {
        $this->mollieModel = $mollieModel;
        $this->escaper = $escaper;
        $this->assetRepository = $assetRepository;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;

        foreach ($this->methodCodes as $code) {
            $this->methods[$code] = $paymentHelper->getMethodInstance($code);
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
        foreach ($this->methodCodes as $code) {
            if ($this->methods[$code]->isAvailable()) {
                $config['payment']['instructions'][$code] = $this->getInstructions($code);
                $config['payment']['image'][$code] = $this->getMethodImage($code);
                if ($code == 'mollie_methods_ideal') {
                    $config['payment']['issuers'] = $this->getIssuers($code);
                }
            }
        }
        
        return $config;
    }

    /**
     * Instruction data
     *
     * @param $code
     * @return string
     */
    protected function getInstructions($code)
    {
        return nl2br($this->escaper->escapeHtml($this->methods[$code]->getInstructions()));
    }

    /**
     * Image Array with payment logo's
     *
     * @param $code
     * @return bool|mixed
     */
    public function getMethodImage($code)
    {
        $images = [];
        $images['mollie_methods_bancontact'] = $this->assetRepository->getUrl("Magmodules_Mollie::images/bancontact.png");
        $images['mollie_methods_banktransfer'] = $this->assetRepository->getUrl("Magmodules_Mollie::images/banktransfer.png");
        $images['mollie_methods_belfius'] = $this->assetRepository->getUrl("Magmodules_Mollie::images/belfius.png");
        $images['mollie_methods_bitcoin'] = $this->assetRepository->getUrl("Magmodules_Mollie::images/bitcoin.png");
        $images['mollie_methods_creditcard'] = $this->assetRepository->getUrl("Magmodules_Mollie::images/creditcard.png");
        $images['mollie_methods_ideal'] = $this->assetRepository->getUrl("Magmodules_Mollie::images/ideal.png");
        $images['mollie_methods_kbc'] = $this->assetRepository->getUrl("Magmodules_Mollie::images/kbc.png");
        $images['mollie_methods_paypal'] = $this->assetRepository->getUrl("Magmodules_Mollie::images/paypal.png");
        $images['mollie_methods_paysafecard'] = $this->assetRepository->getUrl("Magmodules_Mollie::images/paysafecard.png");
        $images['mollie_methods_sofort'] = $this->assetRepository->getUrl("Magmodules_Mollie::images/sofort.png");
        
        if ($this->getStoreConfig(self::XML_PATH_IMAGES)) {
            if (isset($images[$code])) {
                return $images[$code];
            }
        }
        
        return false;
    }

    /**
     * Get Store Config Value
     *
     * @param $path
     * @return mixed
     */
    public function getStoreConfig($path)
    {
        $storeId = $this->storeManager->getStore()->getId();
        return $this->scopeConfig->getValue($path, \Magento\Store\Model\ScopeInterface::SCOPE_STORE, $storeId);
    }

    /**
     * Get iDeal issuers
     *
     * @return array
     */
    public function getIssuers()
    {
        if ($issuers = $this->mollieModel->getIssuers()) {
            return $issuers;
        }
    }
}
