<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\SelfTests;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Mollie\Payment\Helper\General;

class TestIsDobEnabled extends AbstractSelfTest
{
    /**
     * @var ScopeConfigInterface
     */
    private $scopeConfig;

    /**
     * @var General
     */
    private $mollieHelper;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        General $mollieHelper
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->mollieHelper = $mollieHelper;
    }

    public function execute(): void
    {
        if (!$this->mollieHelper->isMethodActive('mollie_methods_in3')) {
            return;
        }

        if ($this->scopeConfig->getValue('customer/address/dob_show') == '') {
            $this->addMessage('error', __(
                'Date of Birth is not enabled in the customer address settings. This is required for IN3. ' .
                'Please enable this in Stores > Configuration > Customers > Customer Configuration > ' .
                'Name and Address Options > Show Date of Birth. Please be also aware that this needs to be available ' .
                'in the checkout.'
            ));
        }
    }
}
