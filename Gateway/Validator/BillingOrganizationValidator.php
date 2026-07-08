<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Sales\Model\Order\Payment as OrderPayment;

class BillingOrganizationValidator extends AbstractValidator
{
    public function validate(array $validationSubject): ResultInterface
    {
        $payment = $validationSubject['payment'] ?? null;
        if (!$payment instanceof OrderPayment) {
            return $this->createResult(true);
        }

        if ($this->hasBillingOrganization($payment)) {
            return $this->createResult(true);
        }

        return $this->createResult(
            false,
            [__('A billing organization name is required for this payment method.')]
        );
    }

    private function hasBillingOrganization(OrderPayment $payment): bool
    {
        return (string)($payment->getOrder()?->getBillingAddress()?->getCompany() ?? '') !== '';
    }
}
