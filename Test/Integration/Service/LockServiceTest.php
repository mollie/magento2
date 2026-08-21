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
use RuntimeException;

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

    public function testKeepsTheUnderlyingLockUntilTheOutermostUnlock(): void
    {
        $lockManager = new LockManagerFake();
        $lockService = $this->createLockService($lockManager);

        $lockService->lock('mollie.order.a');
        $lockService->lock('mollie.order.a');
        $lockService->unlock('mollie.order.a');

        $this->assertTrue($lockService->isLocked('mollie.order.a'));
        $this->assertSame(0, $lockManager->unlockCallCount('mollie.order.a'));

        $lockService->unlock('mollie.order.a');

        $this->assertFalse($lockService->isLocked('mollie.order.a'));
        $this->assertSame(1, $lockManager->unlockCallCount('mollie.order.a'));
    }

    public function testReportsALockThatAnotherProcessHolds(): void
    {
        $lockManager = new LockManagerFake();
        $lockService = $this->createLockService($lockManager);

        $lockManager->lock('mollie.order.a');

        $this->assertTrue($lockService->isLockedByAnotherProcess('mollie.order.a'));
    }

    public function testIgnoresALockThisRequestOwnsItself(): void
    {
        $lockManager = new LockManagerFake();
        $lockService = $this->createLockService($lockManager);

        $lockService->lock('mollie.order.a');

        $this->assertFalse($lockService->isLockedByAnotherProcess('mollie.order.a'));
    }

    public function testDoesNotWaitForALockThisRequestOwnsItself(): void
    {
        $lockManager = new LockManagerFake();
        $lockService = $this->createLockService($lockManager);

        $lockService->lock('mollie.order.a');

        $this->assertFalse($lockService->checkIfIsLockedWithWait('mollie.order.a'));
    }

    public function testReleasesTheLockAfterTheCallbackIsDone(): void
    {
        $lockManager = new LockManagerFake();
        $lockService = $this->createLockService($lockManager);

        $lockedDuringCallback = null;
        $result = $lockService->executeWhileLocked('mollie.order.a', function () use ($lockService, &$lockedDuringCallback) {
            $lockedDuringCallback = $lockService->isLocked('mollie.order.a');

            return 'done';
        });

        $this->assertSame('done', $result);
        $this->assertTrue($lockedDuringCallback);
        $this->assertFalse($lockService->isLocked('mollie.order.a'));
    }

    public function testReleasesTheLockWhenTheCallbackThrows(): void
    {
        $lockManager = new LockManagerFake();
        $lockService = $this->createLockService($lockManager);

        try {
            $lockService->executeWhileLocked('mollie.order.a', function (): void {
                throw new RuntimeException('It failed');
            });

            $this->fail('Expected the callback to throw');
        } catch (RuntimeException $exception) {
            $this->assertSame('It failed', $exception->getMessage());
        }

        $this->assertFalse($lockService->isLocked('mollie.order.a'));
        $this->assertSame(1, $lockManager->unlockCallCount('mollie.order.a'));
    }

    public function testRunsTheCallbackWhenThisRequestAlreadyOwnsTheLock(): void
    {
        $lockManager = new LockManagerFake();
        $lockService = $this->createLockService($lockManager);

        $lockService->lock('mollie.order.a');
        $lockService->executeWhileLocked('mollie.order.a', fn () => null);

        $this->assertTrue($lockService->isLocked('mollie.order.a'));
        $this->assertSame(1, $lockManager->lockCallCount('mollie.order.a'));
        $this->assertSame(0, $lockManager->unlockCallCount('mollie.order.a'));
    }

    private function createLockService(LockManagerFake $lockManager): LockService
    {
        return $this->objectManager->create(LockService::class, ['lockManager' => $lockManager]);
    }
}
