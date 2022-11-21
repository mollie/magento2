<?php declare(strict_types=1);

namespace Mollie\Payment\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

interface ApiKeyFallbackInterface extends ExtensibleDataInterface
{
    const ENTITY_ID = 'entity_id';
    const APIKEY = 'apikey';
    const MODE = 'mode';
    const CREATED_AT = 'created_at';

    /**
     * @return int|null
     */
    public function getEntityId();

    /**
     * @param int $entity_id
     * @return \Mollie\Payment\Api\Data\ApiKeyFallbackInterface
     */
    public function setEntityId(int $entity_id);

    /**
     * @return string
     */
    public function getApikey();

    /**
     * @param string $apikey
     * @return \Mollie\Payment\Api\Data\ApiKeyFallbackInterface
     */
    public function setApikey(string $apikey);

    /**
     * @return string
     */
    public function getMode();

    /**
     * @param string $mode
     * @return \Mollie\Payment\Api\Data\ApiKeyFallbackInterface
     */
    public function setMode(string $mode);

    /**
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * @param string $created_at
     * @return \Mollie\Payment\Api\Data\ApiKeyFallbackInterface
     */
    public function setCreatedAt(string $created_at);

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return \Mollie\Payment\Api\Data\ApiKeyFallbackExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     * @param \Mollie\Payment\Api\Data\ApiKeyFallbackExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Mollie\Payment\Api\Data\ApiKeyFallbackExtensionInterface $extensionAttributes
    );
}
