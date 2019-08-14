<?php

class CornerDrop_Collect_Model_Sales_Order_Invoice_Total_CornerDrop_Fee extends Mage_Sales_Model_Order_Invoice_Total_Abstract {

    /**
     * Set the CornerDrop Fee total.
     *
     * @param Mage_Sales_Model_Order_Invoice $invoice
     * @return $this
     */
    public function collect(Mage_Sales_Model_Order_Invoice $invoice) {
        $order = $invoice->getOrder();

        $cornerdrop_fee = $order->getData(CornerDrop_Collect_Helper_Data::CORNERDROP_FEE_AMOUNT);
        $base_cornerdrop_fee = $order->getData(CornerDrop_Collect_Helper_Data::BASE_CORNERDROP_FEE_AMOUNT);
        $cornerdrop_tax = $order->getData(CornerDrop_Collect_Helper_Data::CORNERDROP_TAX);
        $base_cornerdrop_tax = $order->getData(CornerDrop_Collect_Helper_Data::BASE_CORNERDROP_TAX);

        if ($cornerdrop_fee) {
            // Only add CornerDrop Collect fee to the first invoice
            foreach ($invoice->getOrder()->getInvoiceCollection() as $previous_invoice) {
                if (
                    $previous_invoice->getData(CornerDrop_Collect_Helper_Data::CORNERDROP_FEE_AMOUNT)
                    && !$previous_invoice->isCanceled()
                ) {
                    return $this;
                }
            }

            $invoice->addData(array(
                CornerDrop_Collect_Helper_Data::CORNERDROP_FEE_AMOUNT => $cornerdrop_fee,
                CornerDrop_Collect_Helper_Data::BASE_CORNERDROP_FEE_AMOUNT => $base_cornerdrop_fee,
                CornerDrop_Collect_Helper_Data::CORNERDROP_TAX => $cornerdrop_tax,
                CornerDrop_Collect_Helper_Data::BASE_CORNERDROP_TAX => $base_cornerdrop_tax
            ));

            $invoice->setTaxAmount($invoice->getTaxAmount() + $cornerdrop_tax);
            $invoice->setBaseTaxAmount($invoice->getBaseTaxAmount() + $base_cornerdrop_tax);
            $invoice->setGrandTotal($invoice->getGrandTotal() + $cornerdrop_fee + $cornerdrop_tax);
            $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $base_cornerdrop_fee + $base_cornerdrop_tax);
        }

        return $this;
    }

}
