<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Plugin\Email\Model\Template;

use Magento\Email\Model\Template\Config as Subject;

class Config
{
    /**
     * The required path is calculated automatic by Magento, but this calculation changed between 2.3.3 and 2.3.4.
     * That's why we chance the path manually before we need it.
     *
     * @param Subject $subject
     * @param $templateId
     * @return array
     */
    public function beforeGetTemplateLabel(Subject $subject, $templateId)
    {
        if (strpos($templateId, 'mollie_general_second_chance_email_template') !== false) {
            return ['payment_other_mollie_mollie_general_second_chance_email_template'];
        }

        return $templateId;
    }
}