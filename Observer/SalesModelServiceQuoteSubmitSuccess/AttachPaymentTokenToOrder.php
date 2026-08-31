<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Observer\SalesModelServiceQuoteSubmitSuccess;

use Exception;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Mollie\Payment\Api\Data\PaymentTokenInterface;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\Model\PaymentTokenFactory;

class AttachPaymentTokenToOrder implements ObserverInterface
{
    public function __construct(
        private ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        private PaymentTokenRepositoryInterface $paymentTokenRepository,
        private TransactionFactory $transactionFactory,
        private PaymentTokenFactory $paymentTokenFactory
    ) {}

    /**
     * @param Observer $observer
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer): void
    {
        /* @var OrderInterface $order */
        $order = $observer->getEvent()->getData('order');

        /* @var CartInterface $quote */
        $quote = $observer->getEvent()->getData('quote');

        if (!$order || !$quote) {
            return;
        }

        $tokens = $this->getTokensWithoutOrder($quote);
        if ($tokens === []) {
            return;
        }

        $this->updateModels($tokens, $order);
    }

    /**
     * A quote is reused when the customer retries after an unsuccessful payment, so it can have
     * tokens of previously placed orders attached to it. Those belong to the order they were
     * created for: taking them over would leave that order without a way to validate its return.
     *
     * @param CartInterface $quote
     * @return PaymentTokenInterface[]
     */
    private function getTokensWithoutOrder(CartInterface $quote): array
    {
        /** @var PaymentTokenInterface[] $tokens */
        $tokens = $this->paymentTokenRepository->getByCart($quote)->getItems();

        return array_filter(
            $tokens,
            static fn (PaymentTokenInterface $paymentToken): bool => $paymentToken->getOrderId() === null,
        );
    }

    /**
     * @param PaymentTokenInterface[] $tokens
     * @param OrderInterface $order
     * @throws Exception
     */
    private function updateModels(array $tokens, OrderInterface $order): void
    {
        $transaction = $this->transactionFactory->create();

        foreach ($tokens as $paymentToken) {
            $paymentToken->setOrderId($order->getEntityId());

            $paymentTokenData = $this->extensibleDataObjectConverter->toNestedArray(
                $paymentToken,
                [],
                PaymentTokenInterface::class,
            );

            $paymentTokenModel = $this->paymentTokenFactory->create()->setData($paymentTokenData);

            $transaction->addObject($paymentTokenModel);
        }

        $transaction->save();
    }
}
