<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Block\Form;

use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Block\Form;
use Mollie\Payment\Service\Mollie\Order\Transaction\Expires;

/**
 * Class Paymentlink
 *
 * @package Mollie\Payment\Block\Form
 */
class Paymentlink extends Form
{
    /**
     * @var string
     */
    protected $_template = 'Mollie_Payment::form/mollie_paymentlink.phtml';

    public function __construct(
        Context $context,
        private Expires $expires,
        array $data = [],
    ) {
        parent::__construct($context, $data);
    }

    public function getExpiresAt(): ?string
    {
        $storeId = $this->getRequest()->getParam('store_id');

        $days = $this->expires->getExpiresAtForMethod('paymentlink', $storeId);
        if (!$days) {
            return $days;
        }

        return $this->expires->atDateForMethod('paymentlink');
    }
}
