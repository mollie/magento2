<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Methods;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Method\Logger;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderFactory;
use Mollie\Payment\Config;
use Mollie\Payment\Helper\General as MollieHelper;
use Mollie\Payment\Model\Adminhtml\Source\VoucherCategory;
use Mollie\Payment\Model\Client\Orders as OrdersApi;
use Mollie\Payment\Model\Client\Payments as PaymentsApi;
use Mollie\Payment\Service\Mollie\Timeout;
use Mollie\Payment\Service\Quote\QuoteHasMealVoucherProducts;

class Voucher extends \Mollie\Payment\Model\Mollie
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = 'mollie_methods_voucher';

    /**
     * Info instructions block path
     *
     * @var string
     */
    protected $_infoBlockType = 'Mollie\Payment\Block\Info\Base';

    /**
     * @var QuoteHasMealVoucherProducts
     */
    private $quoteHasMealVoucherProducts;

    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        Logger $logger,
        OrderRepository $orderRepository,
        OrderFactory $orderFactory,
        OrdersApi $ordersApi,
        PaymentsApi $paymentsApi,
        MollieHelper $mollieHelper,
        CheckoutSession $checkoutSession,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AssetRepository $assetRepository,
        ResourceConnection $resourceConnection,
        Config $config,
        Timeout $timeout,
        QuoteHasMealVoucherProducts $quoteHasMealVoucherProducts,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $orderRepository,
            $orderFactory,
            $ordersApi,
            $paymentsApi,
            $mollieHelper,
            $checkoutSession,
            $searchCriteriaBuilder,
            $assetRepository,
            $resourceConnection,
            $config,
            $timeout,
            $resource,
            $resourceCollection,
            $data
        );

        $this->quoteHasMealVoucherProducts = $quoteHasMealVoucherProducts;
    }

    public function isAvailable(CartInterface $quote = null)
    {
        $voucherCategory = $this->config->getVoucherCategory($quote->getStoreId());
        if ($quote && !$voucherCategory) {
            return false;
        }

        if ($voucherCategory == VoucherCategory::CUSTOM_ATTRIBUTE &&
            !$this->quoteHasMealVoucherProducts->check($quote)) {
            return false;
        }

        return parent::isAvailable($quote);
    }
}