<?php declare(strict_types=1);

namespace Mollie\Payment\Model\Data;

use Magento\Framework\Api\AbstractExtensibleObject;
use Mollie\Payment\Api\Data\ApiKeyFallbackExtensionInterface;
use Mollie\Payment\Api\Data\ApiKeyFallbackInterface;

class ApiKeyFallback extends AbstractExtensibleObject implements ApiKeyFallbackInterface
{
    /**
     * Get entity_id
     * @return int|null
     */
    public function getEntityId()
    {
        return $this->_get(self::ENTITY_ID);
    }

    /**
     * Set entity_id
     * @param int $entity_id
     * @return ApiKeyFallbackInterface
     */
    public function setEntityId(int $entity_id)
    {
        return $this->setData(self::ENTITY_ID, $entity_id);
    }

    /**
     * Get apikey
     * @return string|null
     */
    public function getApikey()
    {
        return $this->_get(self::APIKEY);
    }

    /**
     * Set apikey
     * @param string $apikey
     * @return ApiKeyFallbackInterface
     */
    public function setApikey(string $apikey)
    {
        return $this->setData(self::APIKEY, $apikey);
    }

    /**
     * Get mode
     * @return string|null
     */
    public function getMode()
    {
        return $this->_get(self::MODE);
    }

    /**
     * Set mode
     * @param string $mode
     * @return ApiKeyFallbackInterface
     */
    public function setMode(string $mode)
    {
        return $this->setData(self::MODE, $mode);
    }

    /**
     * Get created_at
     * @return string|null
     */
    public function getCreatedAt()
    {
        return $this->_get(self::CREATED_AT);
    }

    /**
     * Set created_at
     * @param string $created_at
     * @return ApiKeyFallbackInterface
     */
    public function setCreatedAt(string $created_at)
    {
        return $this->setData(self::CREATED_AT, $created_at);
    }

    /**
     * Retrieve existing extension attributes object or create a new one.
     * @return ApiKeyFallbackExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * Set an extension attributes object.
     * @param ApiKeyFallbackExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        ApiKeyFallbackExtensionInterface $extensionAttributes
    ) {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
