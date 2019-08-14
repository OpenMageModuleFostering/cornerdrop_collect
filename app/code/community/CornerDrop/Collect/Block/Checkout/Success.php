<?php

class CornerDrop_Collect_Block_Checkout_Success extends Mage_Core_Block_Template
{
    protected $order;

    /**
     * Get the current order model.
     *
     * @return Mage_Sales_Model_Order
     */
    public function getOrder()
    {
        if (!$this->order) {
            if ($orderId = Mage::getSingleton("checkout/session")->getLastOrderId()) {
                $this->order = Mage::getModel("sales/order")->load($orderId);
            }
        }

        return $this->order;
    }

    /**
     * Check if CornerDrop was selected for the current order.
     *
     * @return bool
     */
    public function isCornerDropOrder()
    {
        return $this->getModuleHelper()->isOrderCornerDrop($this->getOrder());
    }

    /**
     * Get CornerDrop logo HTML.
     *
     * @return string
     */
    public function getLogoHtml()
    {
        return $this->getModuleHelper()->getLogoHtml();
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
}
