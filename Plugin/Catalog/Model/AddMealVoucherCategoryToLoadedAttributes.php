<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Plugin\Catalog\Model;

use Magento\Catalog\Model\Attribute\Config;
use Mollie\Payment\Config as MollieConfig;
use Mollie\Payment\Model\Adminhtml\Source\VoucherCategory;

class AddMealVoucherCategoryToLoadedAttributes
{
    /**
     * @var MollieConfig
     */
    private $config;

    public function __construct(
        MollieConfig $config
    ) {
        $this->config = $config;
    }

    /**
     * The attribute that holds the meal voucher category is an attribute that can be selected via the config. With a
     * normal attribute you need to add it to `etc/catalog_attributes.xml` to get it loaded in the quote, but as the
     * attribute is user defined we can't define it. So that's why we add it dynamicly to the list of attributes that
     * needs to get loaded.
     *
     * @param Config $subject
     * @param array $result
     * @param string $groupName
     * @return array
     */
    public function afterGetAttributeNames(Config $subject, $result, $groupName)
    {
        if ($groupName != 'quote_item' || $this->config->getVoucherCategory() != VoucherCategory::CUSTOM_ATTRIBUTE) {
            return $result;
        }

        $result[] = $this->config->getVoucherCustomAttribute();

        return $result;
    }
}