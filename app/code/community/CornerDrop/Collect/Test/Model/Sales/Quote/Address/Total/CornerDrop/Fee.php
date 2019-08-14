<?php

class CornerDrop_Collect_Test_Model_Sales_Quote_Address_Total_CornerDrop_Fee extends EcomDev_PHPUnit_Test_Case {

    /**
     * Magento uses cookies to handle the addresses, PHPUnit
     * never starts a session so the cookie could not be set.
     *
     * @link http://stackoverflow.com/a/23400885/2003205
     */
    public function setUp(){
        parent::setUp();
        if(session_id() !== '') {
            session_destroy();
        }
        @session_start();
    }

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
        $this->assertEquals(0.83, $values['tax']);
        $this->assertEquals(0.83, $values['base_tax']);
    }

    /**
     * Test the CornerDrop fee is added without tax.
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
     * Check the CornerDrop fee is not added to a Quote if it's not
     * selected on checkout.
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
        /** @var CornerDrop_Collect_Model_Sales_Quote_Address_Total_CornerDrop_Fee $total */
        $total          = Mage::getModel('cornerdrop_collect/sales_quote_address_total_cornerdrop_fee');
        $addresses      = Mage::getModel('sales/quote_address')->getCollection()->addFieldToFilter('quote_id', array('eq' => 18))->addFieldToSelect('*');
        $quote          = Mage::getModel('sales/quote')->getCollection()->getFirstItem();
        $total_fee      = 0;
        $base_total_fee = 0;
        $tax            = 0;
        $base_tax       = 0;

        foreach($addresses as $address) {
            /** @ var Mage_Sales_Model_Quote_Address $address */
            $address->setQuote($quote);
            $total->collect($address);
            $total->fetch($address);
            $total_fee      += (float) $address->getData(CornerDrop_Collect_Helper_Data::CORNERDROP_FEE_AMOUNT);
            $base_total_fee += (float) $address->getData(CornerDrop_Collect_Helper_Data::BASE_CORNERDROP_FEE_AMOUNT);
            $tax            += (float) $address->getData(CornerDrop_Collect_Helper_Data::CORNERDROP_TAX);
            $base_tax       += (float) $address->getData(CornerDrop_Collect_Helper_Data::BASE_CORNERDROP_TAX);
        }

        return compact(
            'total_fee',
            'base_total_fee',
            'tax',
            'base_tax'
        );
    }

}
