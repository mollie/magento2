<?php
/*
 * Copyright Magmodules.eu. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Mollie\Payment\Test\Integration\Service\Mollie;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Model\MethodInterface;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\Fake\MockResponse;
use Mollie\Api\Http\Requests\GetPaymentRequest;
use Mollie\Api\MollieApiClient;
use Mollie\Payment\Service\Mollie\FormatExceptionMessages;
use Mollie\Payment\Test\Integration\IntegrationTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class FormatExceptionMessagesTest extends IntegrationTestCase
{
    private const GENERIC_MESSAGE = 'Your payment was not completed. Please try again or select another payment method.';

    public function testReturnsAllowedMessageWhenItMatches(): void
    {
        /** @var FormatExceptionMessages $instance */
        $instance = $this->objectManager->create(FormatExceptionMessages::class);

        $result = $instance->execute(
            new Exception('The billing country is not supported for this payment method.')
        );

        $this->assertSame('The billing country is not supported for this payment method.', $result);
    }

    public function testConvertsKnownMessageToExtendedVersion(): void
    {
        /** @var FormatExceptionMessages $instance */
        $instance = $this->objectManager->create(FormatExceptionMessages::class);

        $result = $instance->execute(
            new Exception('The webhook URL is invalid because it is unreachable from Mollie\'s point of view')
        );

        $this->assertStringContainsString('github.com/mollie/magento2/wiki', $result);
    }

    public function testReturnsTimeoutMessageForCurlError28WithMethodInstance(): void
    {
        /** @var FormatExceptionMessages $instance */
        $instance = $this->objectManager->create(FormatExceptionMessages::class);

        $methodInstance = $this->createMock(MethodInterface::class);
        $methodInstance->method('getTitle')->willReturn('iDEAL');

        $result = $instance->execute(new Exception('cURL error 28: Operation timed out'), $methodInstance);

        $this->assertStringContainsString('Timeout', $result);
        $this->assertStringContainsString('iDEAL', $result);
    }

    public function testReturnsGenericMessageForCurlError28WithoutMethodInstance(): void
    {
        /** @var FormatExceptionMessages $instance */
        $instance = $this->objectManager->create(FormatExceptionMessages::class);

        $result = $instance->execute(new Exception('cURL error 28: Operation timed out'));

        $this->assertSame(self::GENERIC_MESSAGE, $result);
    }

    public function testReturnsGenericMessageForUnknownException(): void
    {
        /** @var FormatExceptionMessages $instance */
        $instance = $this->objectManager->create(FormatExceptionMessages::class);

        $result = $instance->execute(new Exception('Something completely different went wrong'));

        $this->assertSame(self::GENERIC_MESSAGE, $result);
    }

    public function testReturnsTheMessageOfALocalizedException(): void
    {
        /** @var FormatExceptionMessages $instance */
        $instance = $this->objectManager->create(FormatExceptionMessages::class);

        $result = $instance->execute(new LocalizedException(__('A message meant for the customer.')));

        $this->assertSame('A message meant for the customer.', $result);
    }

    public function testReturnsAllowedMessagePassedThroughConstructor(): void
    {
        /** @var FormatExceptionMessages $instance */
        $instance = $this->objectManager->create(FormatExceptionMessages::class, [
            'allowedErrorMessages' => ['A custom allowed error message.'],
        ]);

        $result = $instance->execute(new Exception('A custom allowed error message.'));

        $this->assertSame('A custom allowed error message.', $result);
    }

    public function testReturnsDeclinedMessageForSuspectedFraud(): void
    {
        /** @var FormatExceptionMessages $instance */
        $instance = $this->objectManager->create(FormatExceptionMessages::class);

        $result = $instance->execute(
            $this->createApiException(422, 'Unprocessable Entity', 'The payment was declined due to suspected fraud.')
        );

        $this->assertSame('Your payment was declined. Please select another payment method.', $result);
    }

    /**
     * @dataProvider returnsAMessageForTheFieldOfAValidationErrorProvider
     */
    #[DataProvider('returnsAMessageForTheFieldOfAValidationErrorProvider')]
    public function testReturnsAMessageForTheFieldOfAValidationError(string $field, string $expected): void
    {
        /** @var FormatExceptionMessages $instance */
        $instance = $this->objectManager->create(FormatExceptionMessages::class);

        $result = $instance->execute(
            $this->createApiException(422, 'Unprocessable Entity', 'The field is invalid.', $field)
        );

        $this->assertSame($expected, $result);
    }

    public static function returnsAMessageForTheFieldOfAValidationErrorProvider(): array
    {
        return [
            'billing email' => ['billingAddress.email', 'Please check your email address and try again.'],
            'shipping phone' => ['shippingAddress.phone', 'Please check your phone number and try again.'],
            'billing postal code' => ['billingAddress.postalCode', 'Please check your postal code and try again.'],
            'billing street' => ['billingAddress.streetAndNumber', 'Please check your billing address and try again.'],
            'shipping city' => ['shippingAddress.city', 'Please check your shipping address and try again.'],
            'card token' => ['cardToken', 'Please check your card details and try again.'],
            'unknown field' => ['amount', self::GENERIC_MESSAGE],
            'no field' => ['', self::GENERIC_MESSAGE],
        ];
    }

    public function testReturnsGenericMessageForNonValidationApiErrors(): void
    {
        /** @var FormatExceptionMessages $instance */
        $instance = $this->objectManager->create(FormatExceptionMessages::class);

        $result = $instance->execute(
            $this->createApiException(401, 'Unauthorized Request', 'Missing authentication, or failed to authenticate')
        );

        $this->assertSame(self::GENERIC_MESSAGE, $result);
    }

    private function createApiException(
        int $status,
        string $title,
        string $detail,
        ?string $field = null,
    ): ApiException {
        $client = MollieApiClient::fake([
            GetPaymentRequest::class => MockResponse::error($status, $title, $detail, $field),
        ]);

        try {
            $client->send(new GetPaymentRequest('tr_dummy'));
        } catch (ApiException $exception) {
            return $exception;
        }

        $this->fail('Expected the faked request to result in an ApiException');
    }
}
