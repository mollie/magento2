<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie;

use Mollie\Payment\Config;
use Mollie\Payment\Service\Mollie\SavedCardConsentText;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class SavedCardConsentTextTest extends IntegrationTestCase
{
    private function getInstance(string $template): SavedCardConsentText
    {
        $configMock = $this->createMock(Config::class);
        $configMock->method('creditcardConsentText')->willReturn($template);

        return $this->objectManager->create(SavedCardConsentText::class, [
            'config' => $configMock,
        ]);
    }

    /**
     * @magentoConfigFixture current_store general/store_information/name Acme Shop
     * @magentoConfigFixture current_store trans_email/ident_general/email support@acme.example
     */
    public function testReplacesTradingnameWithStoreName(): void
    {
        $result = $this->getInstance('Authorized by {{tradingname}}.')->execute();

        $this->assertSame('Authorized by Acme Shop.', $result);
    }

    /**
     * @magentoConfigFixture current_store general/store_information/name Acme Shop
     * @magentoConfigFixture current_store trans_email/ident_general/email support@acme.example
     */
    public function testReplacesSupportcontactWithGeneralEmail(): void
    {
        $result = $this->getInstance('Contact us at {{supportcontact}}.')->execute();

        $this->assertSame('Contact us at support@acme.example.', $result);
    }

    /**
     * @magentoConfigFixture current_store general/store_information/name Acme Shop
     * @magentoConfigFixture current_store trans_email/ident_general/email support@acme.example
     */
    public function testConvertsMarkdownLinkToAnchorTag(): void
    {
        $result = $this->getInstance('See our [privacy policy](https://acme.example/privacy).')->execute();

        $this->assertSame('See our <a href="https://acme.example/privacy">privacy policy</a>.', $result);
    }

    /**
     * @magentoConfigFixture current_store general/store_information/name Acme Shop
     * @magentoConfigFixture current_store trans_email/ident_general/email support@acme.example
     */
    public function testConvertsMultipleMarkdownLinks(): void
    {
        $template = 'See [policy](https://acme.example/privacy) or [terms](https://acme.example/terms).';

        $result = $this->getInstance($template)->execute();

        $this->assertSame(
            'See <a href="https://acme.example/privacy">policy</a> or <a href="https://acme.example/terms">terms</a>.',
            $result,
        );
    }

    /**
     * @magentoConfigFixture current_store general/store_information/name Acme Shop
     * @magentoConfigFixture current_store trans_email/ident_general/email support@acme.example
     */
    public function testCombinesAllPlaceholdersAndLink(): void
    {
        $template = 'You authorize {{tradingname}} per our [privacy policy](https://acme.example/privacy). Contact {{supportcontact}}.';

        $result = $this->getInstance($template)->execute();

        $this->assertSame(
            'You authorize Acme Shop per our <a href="https://acme.example/privacy">privacy policy</a>. Contact support@acme.example.',
            $result,
        );
    }

    /**
     * @magentoConfigFixture current_store general/store_information/name Acme Shop
     * @magentoConfigFixture current_store trans_email/ident_general/email support@acme.example
     */
    public function testLeavesPlainTextUntouched(): void
    {
        $result = $this->getInstance('No placeholders here.')->execute();

        $this->assertSame('No placeholders here.', $result);
    }
}
