<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Observer\SalesModelServiceQuoteSubmitSuccess;

use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Api\Data\PaymentTokenInterface;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\Model\PaymentTokenFactory;

class AttachPaymentTokenToOrder implements ObserverInterface
{
    /**
     * @var ExtensibleDataObjectConverter
     */
    private $extensibleDataObjectConverter;

    /**
     * @var PaymentTokenRepositoryInterface
     */
    private $paymentTokenRepository;

    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    /**
     * @var PaymentTokenFactory
     */
    private $paymentTokenFactory;

    public function __construct(
        ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        PaymentTokenRepositoryInterface $paymentTokenRepository,
        TransactionFactory $transactionFactory,
        PaymentTokenFactory $paymentTokenFactory
    ) {
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
        $this->paymentTokenRepository = $paymentTokenRepository;
        $this->transactionFactory = $transactionFactory;
        $this->paymentTokenFactory = $paymentTokenFactory;
    }

    /**
     * @param Observer $observer
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function execute(Observer $observer)
    {
        /* @var OrderInterface $order */
        $order = $observer->getEvent()->getData('order');

        /* @var CartInterface $quote */
        $quote = $observer->getEvent()->getData('quote');

        if (!$order || !$quote) {
            return;
        }

        $tokens = $this->paymentTokenRepository->getByCart($quote);
        if (!$tokens->getTotalCount()) {
            return;
        }

        $this->updateModels($tokens, $order);
    }

    /**
     * @param SearchResultsInterface $tokens
     * @param OrderInterface $order
     * @throws \Exception
     */
    private function updateModels(SearchResultsInterface $tokens, OrderInterface $order)
    {
        $transaction = $this->transactionFactory->create();

        /** @var PaymentTokenInterface $paymentToken */
        foreach ($tokens->getItems() as $paymentToken) {
            $paymentToken->setOrderId($order->getEntityId());

            $paymentTokenData = $this->extensibleDataObjectConverter->toNestedArray(
                $paymentToken,
                [],
                PaymentTokenInterface::class
            );

            $paymentTokenModel = $this->paymentTokenFactory->create()->setData($paymentTokenData);

            $transaction->addObject($paymentTokenModel);
        }

        $transaction->save();
    }
}
