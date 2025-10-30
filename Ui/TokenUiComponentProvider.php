<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Ui;

use Magento\Vault\Api\Data\PaymentTokenInterface;
use Magento\Vault\Model\Ui\TokenUiComponentInterfaceFactory;
use Magento\Vault\Model\Ui\TokenUiComponentProviderInterface;
use Mollie\Payment\Model\Methods\CreditcardVault;

class TokenUiComponentProvider implements TokenUiComponentProviderInterface
{
    public function __construct(
        private TokenUiComponentInterfaceFactory $componentFactory
    ) {}

    public function getComponentForToken(PaymentTokenInterface $paymentToken)
    {
        $jsonDetails = json_decode($paymentToken->getTokenDetails() ?: '{}', true);
        $component = $this->componentFactory->create(
            [
                'config' => [
                    'code' => CreditcardVault::CODE,
                    TokenUiComponentProviderInterface::COMPONENT_DETAILS => $jsonDetails,
                    TokenUiComponentProviderInterface::COMPONENT_PUBLIC_HASH => $paymentToken->getPublicHash(),
                ],
                'name' => 'Mollie_Payment/js/view/payment/method-renderer/vault',
            ],
        );

        return $component;
    }
}
