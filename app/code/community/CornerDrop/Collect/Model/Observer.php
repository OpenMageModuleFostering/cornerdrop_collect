<?php

class CornerDrop_Collect_Model_Observer extends Varien_Object
{

    /**
     * Perform the account status check through the API and, if
     * successful, save the result to system configuration.
     *
     * @event admin_system_config_changed_section_cornerdrop_collect
     *
     * @param Varien_Event_Observer|null $observer
     *
     * @return $this
     */
    public function checkAccountStatus(Varien_Event_Observer $observer = null)
    {
        try {
            $response = Mage::getModel("cornerdrop_collect/api_action_status")->execute(null, null);
        } catch (Exception $e) {
            Mage::logException($e);

            if ($observer instanceof Varien_Event_Observer
                && preg_match("/admin/", $observer->getEvent()->getName())
            ) {
                Mage::getSingleton("adminhtml/session")
                    ->addWarning($this->getHelper()->__("Account status check failed!"));
            }

            return $this;
        }

        if ($response->isSuccessful()) {
            $result = $response->getData("result");
            if (isset($result["creditBalance"]) && is_numeric($result["creditBalance"])) {
                $this->getConfig()
                    ->setAccountBalance($result["creditBalance"])
                    ->setStatusLastChecked("now");
            }
        }

        return $this;
    }

    /**
     * Set the CornerDrop Collect flag on the shipping address if
     * CornerDrop was selected as a destination on the billing step
     * of the checkout.
     *
     * @event controller_action_predispatch_checkout_onepage_saveBilling
     *
     * @param Varien_Event_Observer $observer
     */
    public function setCornerDropCollectFlag(Varien_Event_Observer $observer)
    {
        /** @var Mage_Checkout_OnepageController $controller */
        $controller = $observer->getControllerAction();

        if ($controller->getRequest()->isPost()) {
            $data = $controller->getRequest()->getPost("billing", array());
            $flag = array_key_exists("ship_to_cornerdrop", $data) ? $data["ship_to_cornerdrop"] : null;

            if (!is_null($flag)) {
                $address = $controller->getOnepage()->getQuote()->getShippingAddress();

                $address
                    ->setData(CornerDrop_Collect_Helper_Data::IS_CORNERDROP_COLLECT, $flag ? 1 : 0)
                    ->save();
            }
        }
    }

    /**
     * Process incoming JSON refreshes on the admin order creation page, setting the
     * cornerdrop flag, and reserving the place, if we detect a store id on the address
     *
     * @event adminhtml_sales_order_create_process_data
     *
     * @param Varien_Event_Observer $observer
     * @throws Exception
     */
    public function setAdminCornerDropCollectFlagAndReserve(Varien_Event_Observer $observer)
    {
        /** @var Mage_Adminhtml_Model_Sales_Order_Create $order_create_model */
        $order_create_model = $observer->getData('order_create_model');

        $quote               = $order_create_model->getQuote();
        $shipping_address    = $quote->getShippingAddress();
        $cornerdrop_store_id = $shipping_address->getData(CornerDrop_Collect_Helper_Data::CORNERDROP_STORE_ID_COLUMN);

        $is_cornerdrop_collect = $cornerdrop_store_id && is_numeric($cornerdrop_store_id);

        $shipping_address
            ->setData(CornerDrop_Collect_Helper_Data::IS_CORNERDROP_COLLECT, ($is_cornerdrop_collect) ? 1 : 0)
            ->save();

        $order_create_model->setRecollect(true)->recollectCart();

        if ($is_cornerdrop_collect) {
            $reservation_result = Mage::helper('cornerdrop_collect/order')->reserve($quote, $cornerdrop_store_id);

            if ($reservation_result !== CornerDrop_Collect_Helper_Order::RESULT_SUCCESS) {
                Mage::throwException(Mage::helper('cornerdrop_collect')->__(
                    "Unable to reserve collection at this location.  Please try select another location, or try again later."
                ));
            }
        }
    }

    /**
     * If the CornerDrop flag is set then make a reservation for the selected
     * drop point.
     *
     * @event controller_action_predispatch_checkout_onepage_saveShipping
     *
     * @param Varien_Event_Observer $observer
     */
    public function makeCornerDropCollectReservation(Varien_Event_Observer $observer) {
        /** @var Mage_Checkout_OnepageController $controller */
        $controller = $observer->getControllerAction();

        if ($controller->getRequest()->isPost()) {
            $data = $controller->getRequest()->getPost("shipping", array());

            $is_cornerdrop = array_key_exists("is_cornerdrop_collect", $data) ? $data["is_cornerdrop_collect"] == '1' : false;
            $store_id      = array_key_exists("cornerdrop_store_id", $data) ? intval($data["cornerdrop_store_id"]) : null;

            if ($is_cornerdrop) {
                $quote = $controller->getOnepage()->getQuote();

                $reservation_result = Mage::helper('cornerdrop_collect/order')->reserve($quote, $store_id);

                if ($reservation_result !== CornerDrop_Collect_Helper_Order::RESULT_SUCCESS) {
                    $controller->setFlag('', Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
                    $controller->getResponse()->setBody(Mage::helper('core')->jsonEncode(array(
                        "error"   => 1,
                        "message" => Mage::helper('cornerdrop_collect')->__(
                            "Unable to reserve collection at this location.  Please try select another location, or try again later."
                        )
                    )));
                }
            }
        }
    }

    /**
     * Convert total to line order for Paypal.
     *
     * @event paypal_prepare_line_items
     *
     * @param Varien_Event_Observer $observer
     *
     * @return void
     */
    public function addTotalToPaypal(Varien_Event_Observer $observer)
    {
        /** @var Mage_Paypal_Model_Cart $paypal_cart */
        $paypal_cart = $observer->getPaypalCart();

        $sales_entity = $paypal_cart->getSalesEntity();
        if ($sales_entity instanceof Mage_Sales_Model_Quote) {
            if ($sales_entity->isVirtual()) {
                return;
            }
        } else {
            if ($sales_entity->getIsVirtual() || !$sales_entity->getShippingAddress()) {
                return;
            }
        }

        if ($this->getHelper()->isCornerDropAddress($sales_entity->getShippingAddress())) {
            $paypal_cart->addItem(
                $this->getHelper()->getCornerDropFeeLabel(),
                1,
                $sales_entity->getCornerdropFeeAmount(),
                CornerDrop_Collect_Helper_Data::CORNERDROP_FEE_CODE
            );
        }
    }

    /**
     * Append the CornerDrop address template to the customer
     * address template if the address is a CornerDrop address.
     *
     * @event customer_address_format
     * @param Varien_Event_Observer $observer
     */
    public function addCustomerAddressTemplate(Varien_Event_Observer $observer)
    {
        if ($this->getHelper()->isCornerDropAddress($observer->getAddress())) {
            $formatType = $observer->getType();

            $formatType->setDefaultFormat(sprintf("%s%s",
                $formatType->getDefaultFormat(),
                $this->getConfig()->getAddressTemplate($formatType->getCode())
            ));
        }
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

    /**
     * Get the module helper.
     *
     * @return CornerDrop_Collect_Helper_Data
     */
    protected function getHelper()
    {
        return Mage::helper("cornerdrop_collect");
    }
}
