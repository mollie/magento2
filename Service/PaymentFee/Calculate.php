<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\PaymentFee;

use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Model\Quote\Address\Total;
use Mollie\Payment\Exceptions\UnknownPaymentFeeType;
use Mollie\Payment\Model\Adminhtml\Source\PaymentFeeType;
use Mollie\Payment\Service\Config\PaymentFee;
use Mollie\Payment\Service\PaymentFee\Types\FixedAmount;
use Mollie\Payment\Service\PaymentFee\Types\FixedAmountAndPercentage;
use Mollie\Payment\Service\PaymentFee\Types\Percentage;

class Calculate
{
    /**
     * @var ResultFactory
     */
    private $resultFactory;

    /**
     * @var MaximumSurcharge
     */
    private $maximumSurcharge;

    /**
     * @var PaymentFee
     */
    private $config;

    /**
     * @var FixedAmount
     */
    private $fixedAmount;

    /**
     * @var Percentage
     */
    private $percentage;

    /**
     * @var FixedAmountAndPercentage
     */
    private $fixedAmountAndPercentage;

    /**
     * @var array
     */
    private $cache = [];

    public function __construct(
        ResultFactory $resultFactory,
        MaximumSurcharge $maximumSurcharge,
        PaymentFee $config,
        FixedAmount $fixedAmount,
        Percentage $percentage,
        FixedAmountAndPercentage $fixedAmountAndPercentage
    ) {
        $this->maximumSurcharge = $maximumSurcharge;
        $this->config = $config;
        $this->fixedAmount = $fixedAmount;
        $this->percentage = $percentage;
        $this->fixedAmountAndPercentage = $fixedAmountAndPercentage;
        $this->resultFactory = $resultFactory;
    }

    /**
     * @param CartInterface $cart
     * @param Total $total
     * @return Result
     * @throws UnknownPaymentFeeType
     */
    public function forCart(CartInterface $cart, Total $total)
    {
        $key = $cart->getId() . '-' . $cart->getPayment()->getMethod();
        if (isset($this->cache[$key])) {
            return $this->cache[$key];
        }

        if (!$this->config->isAvailableForMethod($cart)) {
            return $this->resultFactory->create();
        }

        $result = $this->calculatePaymentFee($cart, $total);
        $this->maximumSurcharge->calculate($cart, $result);

        $this->cache[$key] = $result;
        return $result;
    }

    /**
     * @param CartInterface $cart
     * @param Total $total
     * @return Result
     * @throws UnknownPaymentFeeType
     */
    private function calculatePaymentFee(CartInterface $cart, Total $total)
    {
        $paymentFeeType = $this->config->getType($cart);
        if ($paymentFeeType == PaymentFeeType::FIXED_FEE) {
            return $this->fixedAmount->calculate($cart, $total);
        }

        if ($paymentFeeType == PaymentFeeType::PERCENTAGE) {
            return $this->percentage->calculate($cart, $total);
        }

        if ($paymentFeeType == PaymentFeeType::FIXED_FEE_AND_PERCENTAGE) {
            return $this->fixedAmountAndPercentage->calculate($cart, $total);
        }

        throw new UnknownPaymentFeeType(sprintf('Unknown payment fee type: %s', $paymentFeeType));
    }
}
