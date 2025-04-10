<?php

namespace Mollie\Payment\Test\Fakes\Service\Mollie\ApplePay;

use Mollie\Payment\Service\Mollie\ApplePay\Validation;

class FakeValidator extends Validation
{
    public function execute(string $validationUrl, ?string $domain = null): string
    {
        return 'fake-response';
    }
}
