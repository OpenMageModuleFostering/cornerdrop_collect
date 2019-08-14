<?php

class CornerDrop_Collect_Model_Sales_Order_Creditmemo_Total_CornerDrop_Fee extends Mage_Sales_Model_Order_Creditmemo_Total_Abstract
{

    /**
     * Set the CornerDrop Fee total.
     *
     * @param Mage_Sales_Model_Order_Creditmemo $creditmemo
     *
     * @return $this
     */
    public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();

        $cornerdrop_fee = $order->getData(CornerDrop_Collect_Helper_Data::CORNERDROP_FEE_AMOUNT);
        $base_cornerdrop_fee = $order->getData(CornerDrop_Collect_Helper_Data::BASE_CORNERDROP_FEE_AMOUNT);
        $cornerdrop_tax = $order->getData(CornerDrop_Collect_Helper_Data::CORNERDROP_TAX);
        $base_cornerdrop_tax = $order->getData(CornerDrop_Collect_Helper_Data::BASE_CORNERDROP_TAX);

        if ($cornerdrop_fee) {
            $creditmemo->addData(array(
                CornerDrop_Collect_Helper_Data::CORNERDROP_FEE_AMOUNT      => $cornerdrop_fee,
                CornerDrop_Collect_Helper_Data::BASE_CORNERDROP_FEE_AMOUNT => $base_cornerdrop_fee,
                CornerDrop_Collect_Helper_Data::CORNERDROP_TAX             => $cornerdrop_tax,
                CornerDrop_Collect_Helper_Data::BASE_CORNERDROP_TAX        => $base_cornerdrop_tax
            ));

            $creditmemo->setTaxAmount($creditmemo->getTaxAmount() + $cornerdrop_tax);
            $creditmemo->setBaseTaxAmount($creditmemo->getBaseTaxAmount() + $base_cornerdrop_tax);
            $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $cornerdrop_fee + $cornerdrop_tax);
            $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $base_cornerdrop_fee + $base_cornerdrop_tax);
        }

        return $this;
    }

}
