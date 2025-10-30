<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

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
use Mollie\Payment\Api\TransactionToOrderRepositoryInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Helper\General as MollieHelper;
use Mollie\Payment\Model\Adminhtml\Source\VoucherCategory;
use Mollie\Payment\Model\Client\Payments as PaymentsApi;
use Mollie\Payment\Model\Client\Payments\ProcessTransaction as PaymentsProcessTransaction;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Service\Mollie\MollieApiClient;
use Mollie\Payment\Service\OrderLockService;
use Mollie\Payment\Service\Quote\QuoteHasMealVoucherProducts;
use Psr\Log\LoggerInterface;

class Voucher extends Mollie
{
    /**
     * Payment method code
     *
     * @var string
     */
    public const CODE = 'mollie_methods_voucher';

    public function __construct(
        ManagerInterface $eventManager,
        ValueHandlerPoolInterface $valueHandlerPool,
        PaymentDataObjectFactory $paymentDataObjectFactory,
        Registry $registry,
        OrderRepository $orderRepository,
        PaymentsApi $paymentsApi,
        MollieHelper $mollieHelper,
        CheckoutSession $checkoutSession,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AssetRepository $assetRepository,
        Config $config,
        PaymentsProcessTransaction $paymentsProcessTransaction,
        OrderLockService $orderLockService,
        MollieApiClient $mollieApiClient,
        TransactionToOrderRepositoryInterface $transactionToOrderRepository,
        $formBlockType,
        $infoBlockType,
        private QuoteHasMealVoucherProducts $quoteHasMealVoucherProducts,
        ?CommandPoolInterface $commandPool = null,
        ?ValidatorPoolInterface $validatorPool = null,
        ?CommandManagerInterface $commandExecutor = null,
        ?LoggerInterface $logger = null,
    ) {
        parent::__construct(
            $eventManager,
            $valueHandlerPool,
            $paymentDataObjectFactory,
            $registry,
            $orderRepository,
            $paymentsApi,
            $mollieHelper,
            $checkoutSession,
            $searchCriteriaBuilder,
            $assetRepository,
            $config,
            $paymentsProcessTransaction,
            $orderLockService,
            $mollieApiClient,
            $transactionToOrderRepository,
            $formBlockType,
            $infoBlockType,
            $commandPool,
            $validatorPool,
            $commandExecutor,
            $logger,
        );
    }

    public function isAvailable(?CartInterface $quote = null): bool
    {
        $storeId = $quote ? storeId($quote->getStoreId()) : null;
        $voucherCategory = $this->config->getVoucherCategory($storeId);
        if ($quote && !$voucherCategory) {
            return false;
        }

        if (
            $voucherCategory == VoucherCategory::CUSTOM_ATTRIBUTE &&
            !$this->quoteHasMealVoucherProducts->check($quote)
        ) {
            return false;
        }

        return parent::isAvailable($quote);
    }
}
