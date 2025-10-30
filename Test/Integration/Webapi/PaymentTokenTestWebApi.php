<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Webapi;

use Magento\Quote\Model\Quote;
use Mollie\Payment\Api\PaymentTokenRepositoryInterface;
use Mollie\Payment\Webapi\PaymentToken;

class PaymentTokenTestWebApi extends AbstractTestWebApi
{
    /**
     * @var string
     */
    protected $class = PaymentToken::class;

    /**
     * @var string
     */
    protected $methods = ['byToken', 'generateForCustomer', 'generateForGuest'];

    /**
     * @magentoDataFixture Magento/Sales/_files/quote.php
     */
    public function testAddsANewPaymentToken(): void
    {
        $cart = $this->objectManager->create(Quote::class);
        $cart->load('test01', 'reserved_order_id');

        /** @var PaymentToken $instance */
        $instance = $this->objectManager->create(PaymentToken::class);
        $token = $instance->generate($cart);

        $model = $this->objectManager->create(PaymentTokenRepositoryInterface::class)->getByToken($token);

        $this->assertEquals($token, $model->getToken());
    }
}
