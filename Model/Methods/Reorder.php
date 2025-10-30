<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Model\Methods;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Registry;
use Magento\Framework\View\Asset\Repository as AssetRepository;
use Magento\Payment\Gateway\Command\CommandManagerInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Config\ValueHandlerPoolInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectFactory;
use Magento\Payment\Gateway\Validator\ValidatorPoolInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Magento\Sales\Model\OrderRepository;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Payment\Api\TransactionToOrderRepositoryInterface;
use Mollie\Payment\Config;
use Mollie\Payment\Helper\General as MollieHelper;
use Mollie\Payment\Model\Client\Payments as PaymentsApi;
use Mollie\Payment\Model\Client\Payments\ProcessTransaction as PaymentsProcessTransaction;
use Mollie\Payment\Model\Mollie;
use Mollie\Payment\Service\Mollie\MollieApiClient;
use Mollie\Payment\Service\OrderLockService;
use Psr\Log\LoggerInterface;

/**
 * Class Reorder
 *
 * @package Mollie\Payment\Model\Methods
 */
class Reorder extends Mollie
{
    /**
     * Payment method code
     *
     * @var string
     */
    public const CODE = 'mollie_methods_reorder';
    private OrderRepository $orderRepository;

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
        private RequestInterface $request,
        $formBlockType,
        $infoBlockType,
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
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param string $paymentAction
     * @param object $stateObject
     *
     * @throws LocalizedException
     * @throws ApiException
     */
    public function initialize($paymentAction, $stateObject): void
    {
        /** @var Payment $payment */
        $payment = $this->getInfoInstance();

        /** @var Order $order */
        $order = $payment->getOrder();
        $order->setCanSendNewEmailFlag(false);
        $this->orderRepository->save($order);
    }

    public function isAvailable(?CartInterface $quote = null): bool
    {
        return $this->request->getModuleName() == 'mollie' && $this->request->getActionName() == 'markAsPaid';
    }
}
