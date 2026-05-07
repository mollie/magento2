<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\GraphQL\Resolver\Customer;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlAuthorizationException;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Mollie\Payment\Config;
use Mollie\Payment\Service\Mollie\RevokeMandate as RevokeMandateService;

class RevokeMandate implements ResolverInterface
{
    public function __construct(
        private RevokeMandateService $revokeMandate,
        private Config $config,
    ) {}

    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null): array
    {
        if (!$context->getUserId()) {
            throw new GraphQlAuthorizationException(__('The current customer is not authorized.'));
        }

        $storeId = (int)$context->getExtensionAttributes()->getStore()->getId();

        if (!$this->config->creditcardEnableCustomersApi($storeId)) {
            throw new GraphQlInputException(__('Saved cards are not enabled.'));
        }

        $mandateId = (string)($args['mandate_id'] ?? '');
        if (!$mandateId) {
            throw new GraphQlInputException(__('mandate_id is required.'));
        }

        try {
            $this->revokeMandate->execute($mandateId, $storeId);
        } catch (LocalizedException $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }

        return ['success' => true];
    }
}
