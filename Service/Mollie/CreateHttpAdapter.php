<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Service\Mollie;

use Composer\CaBundle\CaBundle;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\RequestOptions;
use Mollie\Api\Contracts\HttpAdapterContract;
use Mollie\Api\Http\Adapter\GuzzleMollieHttpAdapter;

class CreateHttpAdapter
{
    public function __construct(
        private int $timeout = 10,
        private int $connectTimeout = 2
    ) {}

    public function execute(): HttpAdapterContract
    {
        $client = new Client([
            RequestOptions::VERIFY => CaBundle::getBundledCaBundlePath(),
            RequestOptions::TIMEOUT => $this->timeout,
            RequestOptions::CONNECT_TIMEOUT => $this->connectTimeout,
            RequestOptions::HTTP_ERRORS => false,
            'handler' => HandlerStack::create(),
        ]);

        return new GuzzleMollieHttpAdapter($client);
    }
}
