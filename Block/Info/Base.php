<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Block\Info;

use Magento\Payment\Block\Info;
use Magento\Framework\View\Element\Template\Context;
use Mollie\Payment\Helper\General as MollieHelper;

class Base extends Info
{

    /**
     * @var MollieHelper
     */
    private $mollieHelper;

    /**
     * Info constructor.
     *
     * @param MollieHelper $mollieHelper
     * @param Context      $context
     * @param array        $data
     */
    public function __construct(
        MollieHelper $mollieHelper,
        Context $context,
        $data = []
    ) {
        parent::__construct($context, $data);
        $this->mollieHelper = $mollieHelper;
    }

    /**
     * @param null $transport
     *
     * @return $this|\Magento\Framework\DataObject
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        if ($this->_paymentSpecificInformation !== null) {
            return $this->_paymentSpecificInformation;
        }

        $transport = parent::_prepareSpecificInformation($transport);

        if ($this->_appState->getAreaCode() !== \Magento\Backend\App\Area\FrontNameResolver::AREA_CODE) {
            return $transport;
        }

        $showTransactionDetails = $this->mollieHelper->showTransactionDetails();
        if (!$showTransactionDetails) {
            return $transport;
        }

        $transactionDetails = json_decode($this->getInfo()->getAdditionalInformation('details'), true);
        if (!$transactionDetails) {
            return $transport;
        }

        $data = [];
        foreach ($transactionDetails as $k => $v) {
            if ($v !== null && !is_array($v)) {
                $label = ucwords(trim(preg_replace('/(?=[A-Z])/', " $1", $k)));
                $data[(string)__($label)] = $v;
            }
        }

        return $transport->setData(array_merge($data, $transport->getData()));
    }

}