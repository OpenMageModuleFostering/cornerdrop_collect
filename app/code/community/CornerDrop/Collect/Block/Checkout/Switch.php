<?php

class CornerDrop_Collect_Block_Checkout_Switch extends Mage_Checkout_Block_Onepage_Abstract
{
    const SKIN_IMAGE_PATH = 'cornerdrop/collect/images/';

    /**
     * Check if CornerDrop Collect is enabled for this checkout.
     *
     * @return bool
     */
    public function isEnabled()
    {
        return !$this->getQuote()->isVirtual() && $this->getConfig()->isExtensionEnabled();
    }

    /**
     * Get the switch checkbox label.
     *
     * @return string
     */
    public function getCheckboxLabel()
    {
        if ($this->getConfig()->getAdditionalFee()) {
            return $this->getModuleHelper()->__("Click & Collect with %s (+%s)", $this->getModuleHelper()->getLogoHtml(), $this->getConfig()->getFormattedFee());
        } else {
            return $this->getModuleHelper()->__("Click & Collect with %s", $this->getModuleHelper()->getLogoHtml());
        }
    }

    /**
     * Check if CornerDrop Collect is selected for the current quote.
     *
     * @return bool
     */
    public function getValue()
    {
        return $this->getModuleHelper()->isCornerDropAddress($this->getQuote()->getShippingAddress());
    }

    /**
     * Get search template HTML.
     *
     * @return string
     */
    public function getSearchTemplateHtml()
    {
        return $this->getChildHtml('search_template');
    }

    /**
     * Get the URL for the given image in skin.
     *
     * @return string
     */
    public function getImageUrl($filename) {
        return $this->getSkinUrl(static::SKIN_IMAGE_PATH . $filename);
    }

    /**
     * Get the module helper.
     *
     * @return CornerDrop_Collect_Helper_Data
     */
    protected function getModuleHelper()
    {
        return Mage::helper("cornerdrop_collect");
    }

    /**
     * Get the config helper.
     *
     * @return CornerDrop_Collect_Helper_Config
     */
    protected function getConfig()
    {
        return Mage::helper("cornerdrop_collect/config");
    }
}
