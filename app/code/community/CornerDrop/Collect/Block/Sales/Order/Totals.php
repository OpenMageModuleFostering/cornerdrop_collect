<?php
 
class CornerDrop_Collect_Block_Sales_Order_Totals extends Mage_Core_Block_Abstract {

    /**
     * Add the CornerDrop Fee Total to Totals
     *
     * @Return void
     */
    public function initTotals() {
        $helper = Mage::helper('cornerdrop_collect');

        /** @var Mage_Sales_Block_Order_Totals $parent */
        $parent = $this->getParentBlock();
        $source = $parent->getSource();
        $shipping_address = $source->getShippingAddress();
        if($shipping_address && $helper->isCornerDropAddress($source->getShippingAddress())) {
            $parent->addTotalBefore(
                new Varien_Object(array(
                        'code'  => CornerDrop_Collect_Helper_Data::CORNERDROP_FEE_AMOUNT,
                        'value' => $source->getData(CornerDrop_Collect_Helper_Data::CORNERDROP_FEE_AMOUNT),
                        'base_value'=> $source->getData(CornerDrop_Collect_Helper_Data::BASE_CORNERDROP_FEE_AMOUNT),
                        'label' => $helper->getCornerDropFeeLabel()
                    )
                ),
                'shipping'
            );
        }
    }

}
