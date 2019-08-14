<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Block\Form;

use Magento\Framework\View\Element\Template\Context;
use Mollie\Payment\Service\Mollie\Order\Transaction\Expires;

/**
 * Class Paymentlink
 *
 * @package Mollie\Payment\Block\Form
 */
class Paymentlink extends \Magento\Payment\Block\Form
{
    /**
     * @var string
     */
    protected $_template = 'Mollie_Payment::form/mollie_paymentlink.phtml';

    /**
     * @var Expires
     */
    private $expires;

    public function __construct(
        Context $context,
        Expires $expires,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->expires = $expires;
    }

    public function getExpiresAt()
    {
        $storeId = $this->getRequest()->getParam('store_id');

        $days = $this->expires->getExpiresAtForMethod('paymentlink', $storeId);
        if (!$days) {
            return $days;
        }

        return $this->expires->atDateForMethod('paymentlink');
    }
}
