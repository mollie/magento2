<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie;

use Exception;

class Timeout
{
    private ?Exception $lastException = null;

    /**
     * @param callable $callback
     * @return mixed
     * @throws Exception
     */
    public function retry(callable $callback)
    {
        $tries = 0;
        do {
            $result = $this->attemptCallback($callback);
            if ($result !== false) {
                return $result;
            }

            $tries++;
        } while ($tries < 3);

        throw $this->lastException;
    }

    private function attemptCallback(callable $callback)
    {
        try {
            return $callback();
        } catch (Exception $exception) {
            $this->lastException = $exception;
            $this->handle($exception);

            return false;
        }
    }

    private function handle(Exception $exception): void
    {
        if (stripos($exception->getMessage(), 'cURL error 28') !== false) {
            return;
        }

        throw $exception;
    }
}
