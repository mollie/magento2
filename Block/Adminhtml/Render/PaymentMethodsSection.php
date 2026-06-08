<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Block\Adminhtml\Render;

use Magento\Config\Block\System\Config\Form;

class PaymentMethodsSection extends Form
{
    protected $_template = 'Mollie_Payment::system/config/payment-methods/section.phtml';
}
