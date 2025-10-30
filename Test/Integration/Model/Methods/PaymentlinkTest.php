<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Model\Methods;

use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Payment\Model\Methods\Paymentlink;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class PaymentlinkTest extends IntegrationTestCase
{
    protected ?string $instance = Paymentlink::class;

    protected ?string $code = 'paymentlink';

    /**
     * @throws LocalizedException
     * @throws ApiException
     *
     * @magentoDataFixture Magento/Sales/_files/order.php
     * @magentoConfigFixture default_store payment/mollie_methods_paymentlink/order_status_new newPendingStatus
     */
    public function testSetsTheCorrectStatus(): void
    {
        $order = $this->loadOrderById('100000001');

        $status = 'newPendingStatus';

        /** @var Paymentlink $instance */
        $instance = $this->objectManager->get(Paymentlink::class);

        $instance->setInfoInstance($order->getPayment());

        $statusObject = new DataObject();
        $instance->initialize('new', $statusObject);

        $this->assertEquals($status, $statusObject->getData('status'));
    }
}
