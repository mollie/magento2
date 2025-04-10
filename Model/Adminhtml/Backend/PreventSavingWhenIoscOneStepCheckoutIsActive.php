<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Adminhtml\Backend;

use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Value;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Module\Manager;
use Magento\Framework\Registry;

class PreventSavingWhenIoscOneStepCheckoutIsActive extends Value
{
    /**
     * @var Manager
     */
    private $moduleManager;

    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        Manager $moduleManager,
        ?AbstractResource $resource = null,
        ?AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $config, $cacheTypeList, $resource, $resourceCollection, $data);
        $this->moduleManager = $moduleManager;
    }

    public function afterSave()
    {
        if ($this->isValueChanged() && !empty($this->getValue()) && $this->moduleManager->isEnabled('Onestepcheckout_Iosc')) {
            throw new LocalizedException(
                __(
                    'Setting the default method does not work when the One Step Checkout extension is enabled. ' .
                    'Please see Sales -> OneStepCheckout -> Payment method defaults for the same effect.'
                )
            );
        }

        return parent::afterSave();
    }
}
