<?php

namespace Mollie\Payment\Service\Mollie;

class ShouldRedirectToSuccessPage
{
    public function execute(array $result): bool
    {
        if (!isset($result['success'])) {
            return false;
        }

        return (bool)$result['success'];
    }
}
