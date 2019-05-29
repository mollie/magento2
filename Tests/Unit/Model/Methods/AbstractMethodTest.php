<?php


namespace Mollie\Payment\Tests\Unit\Model\Methods;

use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Method;
use Mollie\Api\Endpoints\MethodEndpoint;
use Mollie\Api\Resources\MethodCollection;
use Mollie\Payment\Model\MollieConfigProvider;
use Mollie\Payment\Helper\General as MollieHelper;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\TestCase;

abstract class AbstractMethodTest extends TestCase
{
    /**
     * The class to test.
     *
     * @var Object
     */
    protected $instance;

    /**
     * @var string
     */
    protected $code = '';

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);
    }

    public function testHasAnExistingModel()
    {
        $this->assertTrue(class_exists($this->instance), 'We expect that the class ' . $this->instance . ' exists');
    }

    public function testHasTheCorrectCode()
    {
        /**
         * The parent constructor of this class calls the ObjectManager, which isn't available in unit tests. So skip
         * the constructor.
         */
        $reflection = new \ReflectionClass($this->instance);
        $instance = $reflection->newInstanceWithoutConstructor();

        $this->assertEquals('mollie_methods_' . $this->code, $instance->getCode());
    }

    public function testIsListedAsActiveMethod()
    {
        $scopeConfig = $this->createMock(ScopeConfigInterface::class);
        $scopeConfig->method('getValue')->willReturn(1);

        $context = $this->objectManager->getObject(Context::class, [
            'scopeConfig' => $scopeConfig,
        ]);

        /** @var MollieHelper $helper */
        $helper = $this->objectManager->getObject(MollieHelper::class, [
            'context' => $context,
        ]);

        $methods = $helper->getAllActiveMethods(1);

        if ($this->code == 'paymentlink') {
            $this->assertArrayNotHasKey('mollie_methods_' . $this->code, $methods);
            return;
        }

        $this->assertArrayHasKey('mollie_methods_' . $this->code, $methods);
    }

    public function testThatTheMethodIsActive()
    {
        /** @var Method $method */
        $method = $this->objectManager->getObject(Method::class);
        $method->id = $this->code;
        $method->image = new \stdClass;
        $method->image->size2x = 'http://www.example.com/image.png';

        /** @var MethodCollection $methodCollection */
        $methodCollection = $this->objectManager->getObject(MethodCollection::class);
        $methodCollection[] = $method;

        $mollieApiClient = $this->createMock(MollieApiClient::class);
        $mollieApiClient->methods = $this->createMock(MethodEndpoint::class);
        $mollieApiClient->methods->method('all')->willReturn($methodCollection);

        /** @var MollieConfigProvider $instance */
        $instance = $this->objectManager->getObject(MollieConfigProvider::class);
        $methods = $instance->getActiveMethods($mollieApiClient);

        $this->assertArrayHasKey('mollie_methods_' . $this->code, $methods);
        $this->assertEquals($method->image->size2x, $methods['mollie_methods_' . $this->code]['image']);
    }
}