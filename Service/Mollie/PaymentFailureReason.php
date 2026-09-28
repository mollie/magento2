<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie;

enum PaymentFailureReason: string
{
    case AuthenticationAbandoned = 'authentication_abandoned';
    case AuthenticationFailed = 'authentication_failed';
    case AuthenticationRequired = 'authentication_required';
    case AuthenticationUnavailableAcs = 'authentication_unavailable_acs';
    case CardDeclined = 'card_declined';
    case CardExpired = 'card_expired';
    case InactiveCard = 'inactive_card';
    case InsufficientFunds = 'insufficient_funds';
    case InvalidCvv = 'invalid_cvv';
    case InvalidCardHolderName = 'invalid_card_holder_name';
    case InvalidCardNumber = 'invalid_card_number';
    case InvalidCardType = 'invalid_card_type';
    case PossibleFraud = 'possible_fraud';
    case RefusedByIssuer = 'refused_by_issuer';
    case UnknownReason = 'unknown_reason';
}
