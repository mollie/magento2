<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service;

use Mollie\Payment\Service\LockService;
use Mollie\Payment\Test\Fakes\Framework\Lock\LockManagerFake;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class LockServiceTest extends IntegrationTestCase
{
    public function testLocksEachNameIndependently(): void
    {
        $lockManager = new LockManagerFake();
        $lockService = $this->createLockService($lockManager);

        $lockService->lock('mollie.order.a');
        $lockService->lock('mollie.order.b');

        $this->assertTrue($lockService->isLocked('mollie.order.b'));
    }

    public function testUnlockReleasesOnlyTheGivenName(): void
    {
        $lockManager = new LockManagerFake();
        $lockService = $this->createLockService($lockManager);

        $lockService->lock('mollie.order.a');
        $lockService->lock('mollie.order.b');
        $lockService->unlock('mollie.order.a');

        $this->assertFalse($lockService->isLocked('mollie.order.a'));
        $this->assertTrue($lockService->isLocked('mollie.order.b'));
    }

    public function testAcquiresTheUnderlyingLockOnlyOncePerName(): void
    {
        $lockManager = new LockManagerFake();
        $lockService = $this->createLockService($lockManager);

        $lockService->lock('mollie.order.a');
        $lockService->lock('mollie.order.a');

        $this->assertSame(1, $lockManager->lockCallCount('mollie.order.a'));
    }

    private function createLockService(LockManagerFake $lockManager): LockService
    {
        return $this->objectManager->create(LockService::class, ['lockManager' => $lockManager]);
    }
}
