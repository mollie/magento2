<?php

namespace Mollie\Payment\Service\Mollie\Order;

use Magento\Framework\Exception\LocalizedException;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\PaymentFactory;
use Mollie\Payment\Helper\General as MollieHelper;

class RefundUsingPayment
{
    /**
     * @var PaymentFactory
     */
    private $paymentFactory;

    /**
     * @var MollieHelper
     */
    private $mollieHelper;

    public function __construct(
        PaymentFactory $paymentFactory,
        MollieHelper $mollieHelper
    ) {
        $this->paymentFactory = $paymentFactory;
        $this->mollieHelper = $mollieHelper;
    }

    public function execute(MollieApiClient $mollieApi, $transactionId, $currencyCode, $amount)
    {
        $mollieOrder = $mollieApi->orders->get($transactionId, ['embed' => 'payments']);
        $payments = $mollieOrder->_embedded->payments;

        try {
            $payment = $this->paymentFactory->create([$mollieApi]);
            $payment->id = current($payments)->id;

            $mollieApi->payments->refund($payment, [
                'amount' => [
                    'currency' => $currencyCode,
                    'value' => $this->mollieHelper->formatCurrencyValue(
                        $amount,
                        $currencyCode
                    ),
                ]
            ]);
        } catch (\Exception $exception) {
            $this->mollieHelper->addTolog('error', $exception->getMessage());
            throw new LocalizedException(
                __('Mollie API: %1', $exception->getMessage())
            );
        }
    }
}
