<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie\Wrapper;

use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\Http\Request;
use Mollie\Api\MollieApiClient;

class MollieApiClientFallbackWrapper extends MollieApiClient
{
    use ApiKeyFallbackTrait;

    public function send(Request $request)
    {
        try {
            return parent::send($request);
        } catch (ApiException $exception) {
            if (!in_array($exception->getCode(), [401, 404]) || !$this->fallbackApiKeysInstance) {
                throw $exception;
            }

            if (!$this->updateClient()) {
                throw $exception;
            }

            // Retry with fallback key
            return $this->send($request);
        }
    }
}
