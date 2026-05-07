<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

class TrackingCookies extends AbstractFieldArray
{
    protected function _prepareToRender(): void
    {
        $this->addColumn('cookie_name', [
            'label' => __('Cookie name'),
            'class' => 'required-entry',
        ]);

        $this->addColumn('alias', [
            'label' => __('Alias / query param'),
            'class' => 'required-entry validate-code',
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add cookie');
    }
}
