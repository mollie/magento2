<?php

namespace Mollie\Payment\Test\Integration\Service\Mollie;

use Mollie\Payment\Service\Mollie\ShouldRedirectToSuccessPage;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class ShouldRedirectToSuccessPageTest extends IntegrationTestCase
{
    public function testDoesNotRedirectWhenSuccessIsNotSet(): void
    {
        $instance = $this->objectManager->get(ShouldRedirectToSuccessPage::class);

        $result = $instance->execute([
            // Omitting the success key on purpose
        ]);

        $this->assertFalse($result);
    }

    public function testNotRedirectWhenTheSuccessKeyExistsButIsFalse(): void
    {
        $instance = $this->objectManager->get(ShouldRedirectToSuccessPage::class);

        $result = $instance->execute([
            'success' => false,
        ]);

        $this->assertFalse($result);
    }

    public function testRedirectWhenTheSuccessKeyExistsAndIsTrue(): void
    {
        $instance = $this->objectManager->get(ShouldRedirectToSuccessPage::class);

        $result = $instance->execute([
            'success' => true,
        ]);

        $this->assertTrue($result);
    }
}
