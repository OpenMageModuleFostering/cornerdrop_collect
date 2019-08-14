<?php

class CornerDrop_Collect_Test_Model_Sales_Order_Creditmemo_Total_CornerDrop_Fee extends EcomDev_PHPUnit_Test_Case {

    /**
     * Test the CornerDrop fee is added with tax.
     *
     * @test
     * @loadFixture
     */
    public function testCornerDropFeeAddedWithTax() {
        $values = $this->_getValues();
        $this->assertEquals(10, $values['total_fee']);
        $this->assertEquals(10, $values['base_total_fee']);
        $this->assertEquals(1, $values['tax']);
        $this->assertEquals(1, $values['base_tax']);
    }

    /**
     * Test the CornerDrop Fee is added without tax.
     *
     * @test
     * @loadFixture
     */
    public function testCornerDropFeeAddedWithNoTax() {
        $values = $this->_getValues();
        $this->assertEquals(10, $values['total_fee']);
        $this->assertEquals(10, $values['base_total_fee']);
        $this->assertEquals(0, $values['tax']);
        $this->assertEquals(0, $values['base_tax']);
    }

    /**
     * Check the CornerDrop Fee is not added to the invoice
     * if the order is not a CornerDrop order.
     *
     * @test
     * @loadFixture
     */
    public function testCornerDropFeeNotAdded() {
        $values = $this->_getValues();
        $this->assertEquals(0, $values['total_fee']);
        $this->assertEquals(0, $values['base_total_fee']);
    }

    /**
     * Get the values which vary based on the loaded fixture.
     *
     * @return array
     */
    private function _getValues() {
        /** @var Mage_Sales_Model_Order_Creditmemo $creditmemo */
        $creditmemo        = Mage::getModel('sales/order_creditmemo')->getCollection()->getFirstItem();
        /** @var CornerDrop_Collect_Model_Sales_Order_Creditmemo_Total_CornerDrop_Fee $total */
        $total          = Mage::getModel('cornerdrop_collect/sales_order_creditmemo_total_cornerdrop_fee');

        $total->collect($creditmemo);

        $total_fee      = $creditmemo->getData(CornerDrop_Collect_Helper_Data::CORNERDROP_FEE_AMOUNT);
        $base_total_fee = $creditmemo->getData(CornerDrop_Collect_Helper_Data::BASE_CORNERDROP_FEE_AMOUNT);
        $tax            = $creditmemo->getData(CornerDrop_Collect_Helper_Data::CORNERDROP_TAX);
        $base_tax       = $creditmemo->getData(CornerDrop_Collect_Helper_Data::BASE_CORNERDROP_TAX);


        return compact(
            'total_fee',
            'base_total_fee',
            'tax',
            'base_tax'
        );
    }

}
