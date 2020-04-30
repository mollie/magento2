<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Mollie;

class Timeout
{
    /**
     * @var \Exception
     */
    private $lastException;

    /**
     * @param callable $callback
     * @return mixed
     * @throws \Exception
     */
    public function retry(Callable $callback)
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
        } catch (\Exception $exception) {
            $this->lastException = $exception;
            $this->handle($exception);

            return false;
        }
    }

    private function handle(\Exception $exception)
    {
        if (stripos($exception->getMessage(), 'cURL error 28') !== false) {
            return;
        }

        throw $exception;
    }
}
