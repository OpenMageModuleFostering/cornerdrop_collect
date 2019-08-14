<?php

class CornerDrop_Collect_Test_Model_Sales_Order_Invoice_Total_CornerDrop_Fee extends EcomDev_PHPUnit_Test_Case {

    /**
     * Test the CornerDrop fee is added with tax.
     *
     * @test
     * @loadFixture
     */
    public function testCornerDropFeeAddedWithTax() {
        $order = Mage::getModel("sales/order")->load(1);
        $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice(array());

        $this->assertEquals(10, $invoice->getData(CornerDrop_Collect_Helper_Data::CORNERDROP_FEE_AMOUNT));
        $this->assertEquals(10, $invoice->getData(CornerDrop_Collect_Helper_Data::BASE_CORNERDROP_FEE_AMOUNT));
        $this->assertEquals(1, $invoice->getData(CornerDrop_Collect_Helper_Data::CORNERDROP_TAX));
        $this->assertEquals(1, $invoice->getData(CornerDrop_Collect_Helper_Data::BASE_CORNERDROP_TAX));
    }

    /**
     * Test the CornerDrop fee is added without tax.
     *
     * @test
     * @loadFixture
     */
    public function testCornerDropFeeAddedWithNoTax() {
        $order = Mage::getModel("sales/order")->load(1);
        $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice(array());

        $this->assertEquals(10, $invoice->getData(CornerDrop_Collect_Helper_Data::CORNERDROP_FEE_AMOUNT));
        $this->assertEquals(10, $invoice->getData(CornerDrop_Collect_Helper_Data::BASE_CORNERDROP_FEE_AMOUNT));
        $this->assertEquals(0, $invoice->getData(CornerDrop_Collect_Helper_Data::CORNERDROP_TAX));
        $this->assertEquals(0, $invoice->getData(CornerDrop_Collect_Helper_Data::BASE_CORNERDROP_TAX));
    }

    /**
     * Check the CornerDrop Fee is not added to the invoice
     * if the order is not a CornerDrop order.
     *
     * @test
     * @loadFixture
     */
    public function testCornerDropFeeNotAdded() {
        $order = Mage::getModel("sales/order")->load(1);
        $invoice = Mage::getModel('sales/service_order', $order)->prepareInvoice(array());

        $this->assertEquals(0, $invoice->getData(CornerDrop_Collect_Helper_Data::CORNERDROP_FEE_AMOUNT));
        $this->assertEquals(0, $invoice->getData(CornerDrop_Collect_Helper_Data::BASE_CORNERDROP_FEE_AMOUNT));
        $this->assertEquals(0, $invoice->getData(CornerDrop_Collect_Helper_Data::CORNERDROP_TAX));
        $this->assertEquals(0, $invoice->getData(CornerDrop_Collect_Helper_Data::BASE_CORNERDROP_TAX));
    }

}
