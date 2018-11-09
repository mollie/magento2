<?php
/**
 * Copyright © 2018 Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Model\Methods;

use Mollie\Payment\Model\Mollie;

/**
 * Class Klarna
 *
 * @package Mollie\Payment\Model\Methods
 */
class Klarnapaylater extends Mollie
{

    const METHOD_CODE = 'mollie_methods_klarnapaylater';

    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = self::METHOD_CODE;

    /**
     * Payment Method feature
     *
     * @var bool
     */
    protected $_canAuthorize = true;

    /**
     * Info instructions block path
     *
     * @var string
     */
    protected $_infoBlockType = 'Mollie\Payment\Block\Info\Base';
}
