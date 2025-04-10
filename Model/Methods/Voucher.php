<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Methods;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Config\ValueHandlerPoolInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Gateway\Validator\ValidatorPoolInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\OrderRepository;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderFactory;
use Mollie\Payment\Api\TransactionToOrderRepositoryInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Helper\General as MollieHelper;
use Mollie\Payment\Model\Adminhtml\Source\VoucherCategory;
use Mollie\Payment\Model\Client\Orders as OrdersApi;
use Mollie\Payment\Model\Client\Orders\ProcessTransaction;
use Mollie\Payment\Model\Client\Payments as PaymentsApi;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Service\Mollie\GetApiMethod;
use Mollie\Payment\Service\Mollie\LogException;
use Mollie\Payment\Service\OrderLockService;
use Mollie\Payment\Service\Mollie\MollieApiClient;
use Mollie\Payment\Service\Mollie\Timeout;
use Mollie\Payment\Service\Quote\QuoteHasMealVoucherProducts;
use Psr\Log\LoggerInterface;

class Voucher extends Mollie
{
    /**
     * Payment method code
     *
     * @var string
     */
    const CODE = 'mollie_methods_voucher';

    /**
     * @var QuoteHasMealVoucherProducts
     */
    private $quoteHasMealVoucherProducts;

    public function __construct(
        ManagerInterface $eventManager,
        ValueHandlerPoolInterface $valueHandlerPool,
        PaymentDataObjectFactory $paymentDataObjectFactory,
        Registry $registry,
        OrderRepository $orderRepository,
        OrderFactory $orderFactory,
        OrdersApi $ordersApi,
        PaymentsApi $paymentsApi,
        MollieHelper $mollieHelper,
        CheckoutSession $checkoutSession,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AssetRepository $assetRepository,
        Config $config,
        Timeout $timeout,
        ProcessTransaction $ordersProcessTransaction,
        OrderLockService $orderLockService,
        MollieApiClient $mollieApiClient,
        TransactionToOrderRepositoryInterface $transactionToOrderRepository,
        GetApiMethod $getApiMethod,
        LogException $logException,
        $formBlockType,
        $infoBlockType,
        QuoteHasMealVoucherProducts $quoteHasMealVoucherProducts,
        ?CommandPoolInterface $commandPool = null,
        ?ValidatorPoolInterface $validatorPool = null,
        ?CommandManagerInterface $commandExecutor = null,
        ?LoggerInterface $logger = null
    ) {
        parent::__construct(
            $eventManager,
            $valueHandlerPool,
            $paymentDataObjectFactory,
            $registry,
            $orderRepository,
            $orderFactory,
            $ordersApi,
            $paymentsApi,
            $mollieHelper,
            $checkoutSession,
            $searchCriteriaBuilder,
            $assetRepository,
            $config,
            $timeout,
            $ordersProcessTransaction,
            $orderLockService,
            $mollieApiClient,
            $transactionToOrderRepository,
            $getApiMethod,
            $logException,
            $formBlockType,
            $infoBlockType,
            $commandPool,
            $validatorPool,
            $commandExecutor,
            $logger
        );

        $this->quoteHasMealVoucherProducts = $quoteHasMealVoucherProducts;
    }

    public function isAvailable(?CartInterface $quote = null)
    {
        $storeId = $quote ? $quote->getStoreId() : null;
        $voucherCategory = $this->config->getVoucherCategory($storeId);
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
