<?php

namespace Mollie\Payment\GraphQL\Resolver\Checkout\ApplePay;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Mollie\Payment\Service\Mollie\ApplePay\Validation;

class ApplePayValidation implements ResolverInterface
{
    /**
     * @var Validation
     */
    private $validation;

    public function __construct(
        Validation $validation
    ) {
        $this->validation = $validation;
    }

    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        return ['response' => $this->validation->execute($args['validationUrl'], $args['domain'] ?? null)];
    }
}
