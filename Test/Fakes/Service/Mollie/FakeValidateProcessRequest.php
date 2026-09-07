<?php

/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Fakes\Service\Mollie;

use Magento\Framework\Exception\AuthorizationException;
use Mollie\Payment\Service\Mollie\ValidateProcessRequest;

class FakeValidateProcessRequest extends ValidateProcessRequest
{
    private ?array $response = null;

    private bool $isTokenInvalid = false;

    public function setResponse(array $response): void
    {
        $this->response = $response;
    }

    public function givenTheTokenIsInvalid(): void
    {
        $this->isTokenInvalid = true;
    }

    public function execute(): array
    {
        if ($this->isTokenInvalid) {
            throw new AuthorizationException(__('Invalid payment token'));
        }

        if ($this->response !== null) {
            return $this->response;
        }

        return parent::execute();
    }
}
