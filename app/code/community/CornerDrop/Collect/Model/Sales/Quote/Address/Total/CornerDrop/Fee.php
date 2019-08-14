<?php

class CornerDrop_Collect_Model_Sales_Quote_Address_Total_CornerDrop_Fee extends Mage_Sales_Model_Quote_Address_Total_Abstract {

    /**
     * Total Code name
     *
     * @var string
     */
    protected $_code = CornerDrop_Collect_Helper_Data::CORNERDROP_FEE_CODE;

    /**
     * Set the CornerDrop Fee total.
     *
     * @param Mage_Sales_Model_Quote_Address $address
     * @return $this
     */
    public function collect(Mage_Sales_Model_Quote_Address $address) {
        $config_helper = Mage::helper('cornerdrop_collect/config');

        if (!$this->isCornerDropAvailable($address)) {
            return $this;
        }

        $store = $address->getQuote()->getStore();

        $base_additional_fee = $config_helper->getAdditionalFee($store);
        if(!$base_additional_fee) {
            return $this;
        }

        parent::collect($address);

        $this->_setAmount(0)
            ->_setBaseAmount(0);

        $items = $this->_getAddressItems($address);
        if (!count($items)) {
            return $this;
        }

        if(!$config_helper->isIncludeTax($store)) {
            $custTaxClassId = $address->getQuote()->getCustomerTaxClassId();
            $taxCalculationModel = Mage::getSingleton('tax/calculation');
            $request = $taxCalculationModel->getRateRequest(
                $address,
                $address->getQuote()->getBillingAddress(),
                $custTaxClassId,
                $store
            );

            $request->setProductClassId($config_helper->getTaxClass($store));

            if ($rate = $taxCalculationModel->getRate($request)) {
                $base_cornerdrop_tax = $store->roundPrice($base_additional_fee * $rate/100);
                $cornerdrop_tax = $store->convertPrice($base_cornerdrop_tax);
                $address->setTaxAmount($address->getTaxAmount() + $cornerdrop_tax);
                $address->setBaseTaxAmount($address->getBaseTaxAmount() + $base_cornerdrop_tax);
                $address->setData(CornerDrop_Collect_Helper_Data::CORNERDROP_TAX, $cornerdrop_tax);
                $address->setData(CornerDrop_Collect_Helper_Data::BASE_CORNERDROP_TAX, $base_cornerdrop_tax);
            }
        }
        $additional_fee = $store->convertPrice($base_additional_fee, false);
        $address->setTotalAmount($this->_code, $additional_fee);
        $address->setBaseTotalAmount($this->_code, $base_additional_fee);

        return $this;
    }

    /**
     * @param Mage_Sales_Model_Quote_Address $address
     *
     * @return CornerDrop_Collect_Model_Sales_Quote_Total_Additional_Fee
     */
    public function fetch(Mage_Sales_Model_Quote_Address $address) {
        if ($this->isCornerDropAvailable($address)) {
            $address->addTotal(array(
                'code'  => $this->getCode(),
                'title' => $this->getLabel(),
                'value' => $address->getCornerdropFeeAmount()
            ));
        }

        return $this;
    }

    /**
     * Get CornerDrop Collect additional fee label
     *
     * @return string
     */
    public function getLabel()
    {
        return Mage::helper('cornerdrop_collect')->getCornerDropFeeLabel();
    }

    /**
     * Check if the given address is a CornerDrop Collect address.
     *
     * @param Mage_Sales_Model_Quote_Address $address
     *
     * @return bool
     */
    protected function isCornerDropAvailable($address)
    {
        $is_enabled = Mage::helper("cornerdrop_collect/config")->isExtensionEnabled($address->getQuote()->getStore());
        $is_cornerdrop = Mage::helper("cornerdrop_collect")->isCornerDropAddress($address);

        return $is_enabled && $is_cornerdrop;
    }
}
