<?php

declare(strict_types=1);

namespace Mollie\Payment\Model\Methods;

use Mollie\Payment\Model\Mollie;

/**
 * Class Ideal
 *
 * @package Mollie\Payment\Model\Methods
 */
class Ideal extends Mollie
{
    /**
     * Payment method code
     *
     * @var string
     */
    public const CODE = 'mollie_methods_ideal';
}
