<?php
/**
 * Copyright Magmodules.eu. All rights reserved.
 *  * See COPYING.txt for license details.
 */

namespace Mollie\Payment\Service\Mollie\SelfTests;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Module\Dir\Reader;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Xml\Parser;

/**
 * This class reads the extension_attributes.xml and checks if the extension attributes contains all methods
 * that are required so we can see if `setup:di:compile` has run. For this we use the object manager, as the list of
 * extension attributes can be (and will be) changed over time, and we don't now which classes are required.
 */
class TestExtensionAttributes extends AbstractSelfTest
{
    /**
     * @var Reader
     */
    private $dirReader;

    /**
     * @var Parser
     */
    private $xmlParser;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function __construct(
        Reader $dirReader,
        Parser $xmlParser,
        ObjectManagerInterface $objectManager
    ) {
        $this->dirReader = $dirReader;
        $this->xmlParser = $xmlParser;
        $this->objectManager = $objectManager;
    }

    public function execute(): void
    {
        if (!$this->allExtensionAttributesExists()) {
            $message = __('Error: It looks like not all extension attributes are present. Make sure you run `bin/magento setup:di:compile`.');
            $this->addMessage('error', $message);
        }
    }

    private function allExtensionAttributesExists()
    {
        $interfaces = $this->getExtensionAttributesList();

        foreach ($interfaces as $interface => $attributes) {
            $instance = $this->objectManager->get($interface);
            $extensionAttributesInstance = $instance->getExtensionAttributes();

            if ($extensionAttributesInstance === null) {
                return false;
            }

            if (!$this->allMethodsExists($extensionAttributesInstance, $attributes)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array
     */
    private function getExtensionAttributesList()
    {
        $output = [];
        $path = $this->dirReader->getModuleDir('etc', 'Mollie_Payment') . '/extension_attributes.xml';
        $dom = $this->xmlParser->load($path)->getDom();
        $xpath = new \DOMXPath($dom);

        $elements = $xpath->query('//config/extension_attributes');

        /** @var \DOMElement $element */
        foreach ($elements as $element) {
            $for = $element->getAttribute('for');
            $output[$for] = $this->getAttributes($element);
        }

        return $output;
    }

    /**
     * @param \DOMElement $element
     * @return array
     */
    private function getAttributes(\DOMElement $element): array
    {
        $attributes = $element->childNodes;
        $codes = [];
        foreach ($attributes as $attribute) {
            if ($attribute->nodeName != 'attribute') {
                continue;
            }

            $codes[] = $attribute->getAttribute('code');
        }
        return $codes;
    }

    private function allMethodsExists($extensionAttributesInstance, $attributes)
    {
        foreach ($attributes as $attribute) {
            if (!method_exists($extensionAttributesInstance, $this->convertToMethodName($attribute))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Convert `mollie_payment_fee` to `getMolliePaymentFee`.
     *
     * @param $attribute
     * @return string
     */
    private function convertToMethodName($attribute)
    {
        return 'get' . str_replace('_', '', ucwords($attribute, '_'));
    }
}
