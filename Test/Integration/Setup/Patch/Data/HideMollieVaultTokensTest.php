<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Setup\Patch\Data;

use Magento\Framework\App\ResourceConnection;
use Mollie\Payment\Setup\Patch\Data\HideMollieVaultTokens;
use Mollie\Payment\Test\Integration\IntegrationTestCase;

class HideMollieVaultTokensTest extends IntegrationTestCase
{
    /**
     * @magentoDbIsolation enabled
     */
    public function testHidesLeftoverCreditcardTokens(): void
    {
        $this->insertToken('mollie_methods_creditcard', 'mdt_creditcard');

        $this->applyPatch();

        $this->assertSame(0, (int)$this->readToken('mdt_creditcard')['is_visible']);
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testHidesLeftoverVaultMethodTokens(): void
    {
        $this->insertToken('mollie_methods_creditcard_vault', 'mdt_vault');

        $this->applyPatch();

        $this->assertSame(0, (int)$this->readToken('mdt_vault')['is_visible']);
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testLeavesTokensOfOtherPaymentMethodsUntouched(): void
    {
        $this->insertToken('braintree', 'braintree_token');

        $this->applyPatch();

        $this->assertSame(1, (int)$this->readToken('braintree_token')['is_visible']);
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testKeepsTheRowSoTheMollieMandateStaysAvailable(): void
    {
        $this->insertToken('mollie_methods_creditcard', 'mdt_12345');

        $this->applyPatch();

        $token = $this->readToken('mdt_12345');

        $this->assertNotSame([], $token);
        $this->assertSame('mdt_12345', $token['gateway_token']);
        $this->assertSame(1, (int)$token['is_active']);
    }

    /**
     * @magentoDbIsolation enabled
     */
    public function testCanBeAppliedMoreThanOnce(): void
    {
        $this->insertToken('mollie_methods_creditcard', 'mdt_repeat');

        $this->applyPatch();
        $this->applyPatch();

        $this->assertSame(0, (int)$this->readToken('mdt_repeat')['is_visible']);
    }

    private function insertToken(string $paymentMethodCode, string $gatewayToken): void
    {
        $connection = $this->objectManager->get(ResourceConnection::class)->getConnection();

        $connection->insert($connection->getTableName('vault_payment_token'), [
            'public_hash' => hash('sha256', $gatewayToken),
            'payment_method_code' => $paymentMethodCode,
            'type' => 'card',
            'gateway_token' => $gatewayToken,
            'expires_at' => '2099-01-01 00:00:00',
            'is_active' => 1,
            'is_visible' => 1,
        ]);
    }

    private function readToken(string $gatewayToken): array
    {
        $connection = $this->objectManager->get(ResourceConnection::class)->getConnection();

        $select = $connection->select()
            ->from($connection->getTableName('vault_payment_token'))
            ->where('gateway_token = ?', $gatewayToken);

        return $connection->fetchRow($select) ?: [];
    }

    private function applyPatch(): void
    {
        $this->objectManager->create(HideMollieVaultTokens::class)->apply();
    }
}
