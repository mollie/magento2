<?php

namespace Mollie\Payment\Service\Mollie;

class ShouldRedirectToSuccessPage
{
    public function execute(GetMollieStatusResult $result): bool
    {
        return in_array($result->getStatus(), ['paid', 'authorized']);
    }
}
