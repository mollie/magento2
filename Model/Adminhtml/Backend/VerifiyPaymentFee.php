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
use Magento\Framework\Exception\ValidatorException;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\Registry;

class VerifiyPaymentFee extends Value
{
    const MAXIMUM_PAYMENT_FEE_AMOUNT = 1.95;

    /**
     * @var PriceCurrencyInterface
     */
    private $priceCurrency;

    public function __construct(
        Context $context,
        Registry $registry,
        ScopeConfigInterface $config,
        TypeListInterface $cacheTypeList,
        PriceCurrencyInterface $priceCurrency,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $config,
            $cacheTypeList,
            $resource,
            $resourceCollection,
            $data
        );

        $this->priceCurrency = $priceCurrency;
    }

    public function beforeSave()
    {
        $value = $this->getValue();
        $this->setValue(str_replace(',', '.', $value));

        if ((double)$value > static::MAXIMUM_PAYMENT_FEE_AMOUNT) {
            $message = __(
                'Please make sure the payment surcharge does not exceed %1.',
                $this->priceCurrency->format(static::MAXIMUM_PAYMENT_FEE_AMOUNT)
            );

            throw new ValidatorException($message);
        }
    }
}
