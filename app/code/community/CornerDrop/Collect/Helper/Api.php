<?php

class CornerDrop_Collect_Helper_Api extends Mage_Core_Helper_Abstract
{

    const DEFAULT_API_IDENTIFIER = 'MAGENTO';

    const STATUS_CODE_SUCCESS                                            = "2000";
    const STATUS_CODE_INVALID_HEADER_INFORMATION                         = "4001";
    const STATUS_CODE_API_KEY_NOT_SUPPLIED                               = "4010";
    const STATUS_CODE_API_KEY_INVALID                                    = "4011";
    const STATUS_CODE_AUTHORIZATION_DENIED                               = "4030";
    const STATUS_CODE_PACKAGE_ACTIVATION_NO_CREDITS                      = "4020";
    const STATUS_CODE_PACKAGE_ACTIVATION_DROP_POINT_NO_CAPACITY          = "4021";
    const STATUS_CODE_PACKAGE_ACTIVATION_DROP_POINT_DAILY_LIMIT_EXCEEDED = "4022";
    const STATUS_CODE_REFERENCE_NOT_FOUND                                = "4041";
    const STATUS_CODE_TERMINATE                                          = "4100";

    /**
     * Return the current version of the module
     *
     * @return string
     */
    public function getVersion()
    {
        return Mage::getConfig()->getModuleConfig('CornerDrop_Collect')->version;
    }

    public function getApiIdentifier()
    {
        return self::DEFAULT_API_IDENTIFIER . '-' . $this->getVersion();
    }

    /**
     * Build & prepare API endpoint url.
     * @param string $endpoint
     * @param Mage_Core_Model_Store|int|null $store_id
     * @return string
     */
    public function buildEndpoint($endpoint, $store_id)
    {
        $uri = Mage::helper('cornerdrop_collect/config')->getApiUri($store_id);
        return rtrim($uri, '/') . $endpoint;
    }
}
