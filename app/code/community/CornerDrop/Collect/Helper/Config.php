<?php

class CornerDrop_Collect_Helper_Config extends Mage_Core_Helper_Abstract
{

    const XML_PATH_STATUS_BALANCE = 'cornerdrop_collect/status/balance';
    const XML_PATH_STATUS_LAST_CHECKED = 'cornerdrop_collect/status/last_checked';

    const XML_PATH_SETTINGS_ENABLED = 'cornerdrop_collect/settings/enable';
    const XML_PATH_SETTINGS_API_KEY = 'cornerdrop_collect/settings/api_key';
    const XML_PATH_SETTINGS_API_URI = 'cornerdrop_collect/settings/api_uri';
    const XML_PATH_SETTINGS_CODE_EMAIL_TEMPLATE = 'cornerdrop_collect/settings/code_email_template';
    const XML_PATH_FEE_SETTINGS_ADDITIONAL_FEE = 'cornerdrop_collect/fee_settings/additional_fee';
    const XML_PATH_FEE_SETTINGS_INCLUDE_TAX = 'cornerdrop_collect/fee_settings/include_tax';
    const XML_PATH_FEE_SETTINGS_TAX_CLASS = 'cornerdrop_collect/fee_settings/tax_class';

    const XML_PATH_PREFIX_ADDRESS_TEMPLATE = 'cornerdrop_collect/address_templates/';

    const DATETIME_FORMAT = "Y-m-d H:i:s T";

    /**
     * Set the account credit balance.
     *
     * @param int $value
     *
     * @return $this
     */
    public function setAccountBalance($value)
    {
        $this->setGlobalConfig(static::XML_PATH_STATUS_BALANCE, $value);

        return $this;
    }

    /**
     * Get the account credit balance.
     *
     * @return int
     */
    public function getAccountBalance()
    {
        $balance = Mage::getStoreConfig(static::XML_PATH_STATUS_BALANCE);

        $balance = (is_numeric($balance)) ? intval($balance) : null;

        return $balance;
    }

    /**
     * Get the account credit balance after taking into account
     * any CornerDrop orders that the API has not been notified about
     * yet.
     *
     * @return int
     */
    public function getAvailableAccountBalance()
    {
        $api_balance = $this->getAccountBalance();

        $pending_orders = Mage::helper("cornerdrop_collect/order")->getCornerDropOrderCollection()
            ->addFieldToFilter(CornerDrop_Collect_Helper_Data::ORDERED_NOTIFICATION_COLUMN, array("neq" => 1))
            ->getSize();

        $balance = $api_balance - $pending_orders;

        return ($balance > 0) ? $balance : 0;
    }

    /**
     * Set the time when the account status was last checked.
     * Uses admin store timezone.
     *
     * @param DateTime|string $datetime
     *
     * @return $this
     */
    public function setStatusLastChecked($datetime = "now")
    {
        if (!$datetime instanceof DateTime) {
            $datetime = new DateTime($datetime, $this->getAdminTimezone());
        }

        $this->setGlobalConfig(static::XML_PATH_STATUS_LAST_CHECKED, $datetime->format(static::DATETIME_FORMAT));

        return $this;
    }

    /**
     * Get the time when the account status was last checked.
     * Uses admin store timezone.
     *
     * @return DateTime|null
     */
    public function getStatusLastChecked()
    {
        $value = Mage::getStoreConfig(static::XML_PATH_STATUS_LAST_CHECKED);

        return ($value) ? DateTime::createFromFormat(static::DATETIME_FORMAT, $value, $this->getAdminTimezone()) : null;
    }

    /**
     * Get the System Configuration setting to enable the module.
     *
     * @param Mage_Core_Model_Store|int|null $store_id
     *
     * @return bool
     */
    public function getEnabledFlag($store_id = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_SETTINGS_ENABLED, $store_id);
    }

    /**
     * Check if the extension functionality is enabled.
     * Includes checking the enabled flag in the configuration and
     * the available account balance.
     *
     * @param Mage_Core_Model_Store|int|null $store_id
     *
     * @return bool
     */
    public function isExtensionEnabled($store_id = null)
    {
        return $this->getEnabledFlag($store_id) && $this->getAvailableAccountBalance() > 0;
    }

    /**
     * @param Mage_Core_Model_Store|int|null $store_id
     *
     * @return string
     */
    public function getApiKey($store_id = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_SETTINGS_API_KEY, $store_id);
    }

    /**
     * Get the email template to use for the package collection
     * code email.
     *
     * @param Mage_Core_Model_Store|int|null $store
     *
     * @return string
     */
    public function getCodeEmailTemplate($store = null)
    {
        return Mage::getStoreConfig(static::XML_PATH_SETTINGS_CODE_EMAIL_TEMPLATE, $store);
    }

    /**
     * @param Mage_Core_Model_Store|int|null $store_id
     *
     * @return string
     */
    public function getAdditionalFee($store_id = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_FEE_SETTINGS_ADDITIONAL_FEE, $store_id);
    }

    /**
     * Get the configured additional fee for the given store, formatted for printing.
     *
     * @param Mage_Core_Model_Store|int|null $store
     *
     * @return string
     */
    public function getFormattedFee($store = null)
    {
        $store = Mage::app()->getStore($store);

        return $store->formatPrice($this->getAdditionalFee($store), false);
    }

    /**
     * @param Mage_Core_Model_Store|int|null $store_id
     *
     * @return bool
     */
    public function isIncludeTax($store_id = null)
    {
        return Mage::getStoreConfigFlag(self::XML_PATH_FEE_SETTINGS_INCLUDE_TAX, $store_id);
    }

    /**
     * @param Mage_Core_Model_Store|int|null $store_id
     *
     * @return string
     */
    public function getTaxClass($store_id = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_FEE_SETTINGS_TAX_CLASS, $store_id);
    }

    /**
     * Fetch the URI used API calls.
     *
     * @param Mage_Core_Model_Store|int|null $store_id
     *
     * @return string
     */
    public function getApiUri($store_id = null)
    {
        return Mage::getStoreConfig(self::XML_PATH_SETTINGS_API_URI, $store_id);
    }

    /**
     * Get the customer address template suffix for the specified address format.
     *
     * @param string                         $type_code
     * @param Mage_Core_Model_Store|int|null $store_id
     *
     * @return mixed
     */
    public function getAddressTemplate($type_code, $store_id = null)
    {
        return Mage::getStoreConfig(sprintf("%s%s", static::XML_PATH_PREFIX_ADDRESS_TEMPLATE, $type_code), $store_id);
    }

    /**
     * Set the value of the given System Configuraiton path in the global scope.
     *
     * @param string $path
     * @param string $value
     *
     * @return $this
     */
    protected function setGlobalConfig($path, $value)
    {
        Mage::getConfig()
            ->saveConfig($path, $value, "default")
            ->reinit();

        return $this;
    }

    /**
     * Get the timezone for the admin store.
     *
     * @return DateTimeZone
     */
    protected function getAdminTimezone()
    {
        return new DateTimeZone(
            Mage::app()
                ->getStore(Mage_Core_Model_Store::ADMIN_CODE)
                ->getConfig(Mage_Core_Model_Locale::XML_PATH_DEFAULT_TIMEZONE)
        );
    }

}
