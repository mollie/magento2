<?php

declare(strict_types=1);

namespace Mollie\Payment\Model\Methods;

use Mollie\Payment\Model\Mollie;

/**
 * Class Directdebit
 *
 * @package Mollie\Payment\Model\Methods
 */
class Directdebit extends Mollie
{
    /**
     * Payment method code
     *
     * @var string
     */
    public const CODE = 'mollie_methods_directdebit';
}
