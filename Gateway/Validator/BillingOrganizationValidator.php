<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Model\InfoInterface;
use Magento\Quote\Model\Quote\Payment as QuotePayment;
use Magento\Sales\Model\Order\Payment as OrderPayment;

class BillingOrganizationValidator extends AbstractValidator
{
    public function validate(array $validationSubject): ResultInterface
    {
        if ($this->hasBillingOrganization($validationSubject['payment'] ?? null)) {
            return $this->createResult(true);
        }

        return $this->createResult(
            false,
            [__('A billing organization name is required for this payment method.')]
        );
    }

    private function hasBillingOrganization(?InfoInterface $payment): bool
    {
        return $this->getCompany($payment) !== '';
    }

    private function getCompany(?InfoInterface $payment): string
    {
        $billingAddress = match (true) {
            $payment instanceof OrderPayment => $payment->getOrder()?->getBillingAddress(),
            $payment instanceof QuotePayment => $payment->getQuote()?->getBillingAddress(),
            default => null,
        };

        return (string)($billingAddress?->getCompany() ?? '');
    }
}
