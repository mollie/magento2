<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Model\Adminhtml\Backend;

use Magento\Framework\App\Config\MutableScopeConfigInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Api\StoreRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Website;
use Mollie\Api\Fake\MockResponse;
use Mollie\Api\Http\Requests\GetProfileRequest;
use Mollie\Payment\Config;
use Mollie\Payment\Model\Adminhtml\Backend\ChangeApiMode;
use Mollie\Payment\Model\Adminhtml\Backend\UpdateProfileId;
use Mollie\Payment\Test\Fakes\Service\Mollie\FakeMollieApiClient;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

/**
 * @magentoAppArea adminhtml
 */
class ChangeApiModeTest extends IntegrationTestCase
{
    private ?Website $website = null;

    protected function tearDownWithoutVoid()
    {
        $this->objectManager->get(WriterInterface::class)->delete(
            Config::GENERAL_PROFILEID,
            ScopeInterface::SCOPE_WEBSITES,
            (int) $this->website?->getId(),
        );

        $this->website?->delete();
    }

    /**
     * A website whose id does not match any existing store id is exactly the precondition from
     * mollie/magento2#1079: it happens naturally once a store view has been deleted somewhere,
     * leaving a gap in the store id sequence that a later website can fall into.
     */
    public function testChangingModusOnWebsiteScopeDoesNotThrow(): void
    {
        $website = $this->createWebsiteWithoutAnyStore();
        $this->assertNoStoreSharesId((int) $website->getId());

        $this->setApiKeyOnWebsiteScope($website, 'live_apikey123456789101112131415161718');

        $instance = $this->createChangeApiModeWithFakeProfileResponse('pfl_test1079');
        $instance->setScope(ScopeInterface::SCOPE_WEBSITES);
        $instance->setScopeId((int) $website->getId());
        $instance->setScopeCode($website->getCode());
        $instance->setPath(Config::GENERAL_TYPE);
        $instance->setValue('live');

        $instance->afterSave();

        $this->addToAssertionCount(1);
    }

    public function testChangingModusOnWebsiteScopeRefreshesTheProfileIdOnThatSameScope(): void
    {
        $website = $this->createWebsiteWithoutAnyStore();
        $this->assertNoStoreSharesId((int) $website->getId());

        $this->setApiKeyOnWebsiteScope($website, 'live_apikey123456789101112131415161718');

        $instance = $this->createChangeApiModeWithFakeProfileResponse('pfl_test1079');
        $instance->setScope(ScopeInterface::SCOPE_WEBSITES);
        $instance->setScopeId((int) $website->getId());
        $instance->setScopeCode($website->getCode());
        $instance->setPath(Config::GENERAL_TYPE);
        $instance->setValue('live');

        $instance->afterSave();

        $scopeConfig = $this->objectManager->get(ScopeConfigInterface::class);
        $scopeConfig->clean();
        $profileId = $scopeConfig->getValue(
            Config::GENERAL_PROFILEID,
            ScopeInterface::SCOPE_WEBSITES,
            (int) $website->getId(),
        );

        $this->assertSame('pfl_test1079', $profileId);
    }

    /**
     * afterSave() has no isValueChanged() guard by default, so it would call the Mollie API and
     * rewrite the profile id on every save of the section, not only when Modus actually changes.
     */
    public function testDoesNotCallMollieWhenModusHasNotChanged(): void
    {
        $website = $this->createWebsiteWithoutAnyStore();
        $this->assertNoStoreSharesId((int) $website->getId());

        $this->setApiKeyOnWebsiteScope($website, 'live_apikey123456789101112131415161718');
        $this->objectManager->get(MutableScopeConfigInterface::class)->setValue(
            Config::GENERAL_TYPE,
            'live',
            ScopeInterface::SCOPE_WEBSITES,
            $website->getCode(),
        );

        $instance = $this->createChangeApiModeThatFailsIfMollieIsCalled();
        $instance->setScope(ScopeInterface::SCOPE_WEBSITES);
        $instance->setScopeId((int) $website->getId());
        $instance->setScopeCode($website->getCode());
        $instance->setPath(Config::GENERAL_TYPE);
        $instance->setValue('live');

        $instance->afterSave();

        $this->addToAssertionCount(1);
    }

    private function createChangeApiModeThatFailsIfMollieIsCalled(): ChangeApiMode
    {
        $fakeMollieApiClient = $this->objectManager->create(FakeMollieApiClient::class);
        $fakeMollieApiClient->fake([]);

        $updateProfileId = $this->objectManager->create(UpdateProfileId::class, [
            'mollieApiClient' => $fakeMollieApiClient,
        ]);

        return $this->objectManager->create(ChangeApiMode::class, [
            'updateProfileId' => $updateProfileId,
        ]);
    }

    private function createWebsiteWithoutAnyStore(): Website
    {
        /** @var Website $website */
        $website = $this->objectManager->create(Website::class);
        $website->setCode('mollie1079test' . uniqid())->setName('Mollie 1079 Test Website');
        $website->save();

        $this->website = $website;

        return $website;
    }

    private function createChangeApiModeWithFakeProfileResponse(string $profileId): ChangeApiMode
    {
        $fakeMollieApiClient = $this->objectManager->create(FakeMollieApiClient::class);
        $fakeMollieApiClient->fake([
            GetProfileRequest::class => MockResponse::ok(['id' => $profileId]),
        ]);

        $updateProfileId = $this->objectManager->create(UpdateProfileId::class, [
            'mollieApiClient' => $fakeMollieApiClient,
        ]);

        return $this->objectManager->create(ChangeApiMode::class, [
            'updateProfileId' => $updateProfileId,
        ]);
    }

    private function setApiKeyOnWebsiteScope(Website $website, string $apiKey): void
    {
        $this->objectManager->get(MutableScopeConfigInterface::class)->setValue(
            Config::GENERAL_APIKEY_LIVE,
            $apiKey,
            ScopeInterface::SCOPE_WEBSITES,
            $website->getCode(),
        );
    }

    private function assertNoStoreSharesId(int $websiteId): void
    {
        try {
            $this->objectManager->get(StoreRepositoryInterface::class)->getById($websiteId);
        } catch (NoSuchEntityException) {
            return;
        }

        $this->fail(
            'Precondition not met: a store already exists with the same id as the newly created website, '
            . 'so this test no longer reproduces the id collision from mollie/magento2#1079.',
        );
    }
}
