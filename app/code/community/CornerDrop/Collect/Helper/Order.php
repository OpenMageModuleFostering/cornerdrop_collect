<?php

class CornerDrop_Collect_Helper_Order extends Mage_Core_Helper_Abstract
{
    const RESULT_SUCCESS = 1;
    const RESULT_FAILED = 2;
    const RESULT_TERMINATE = 3;

    /**
     * Get an order collection filtered to only include CornerDrop orders.
     *
     * @return Mage_Sales_Model_Resource_Order_Collection
     */
    public function getCornerDropOrderCollection()
    {
        $collection = Mage::getModel("sales/order")->getCollection()
            ->addAddressFields()
            ->addFieldToFilter("shipping_o_a." . CornerDrop_Collect_Helper_Data::IS_CORNERDROP_COLLECT, 1);

        return $collection;
    }

    /**
     * Place a reservation for our quote against the CornerDrop API.  If successful, assign
     * the returned `addressCodeId` to the quote for submission during the order creation
     * call.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param $drop_point_node_id
     *
     * @return int
     */
    public function reserve(Mage_Sales_Model_Quote $quote, $drop_point_node_id)
    {
        try {
            $response = Mage::getModel('cornerdrop_collect/api_action_orderReserve')->execute(
                $quote->getId(),
                $quote->getRemoteIp(),
                array(
                    "dropPointNodeId" => $drop_point_node_id
                )
            );
        } catch (Exception $e) {
            Mage::logException($e);

            return static::RESULT_FAILED;
        }

        if ($response->isSuccessful()) {
            $response_data = $response->getResult();
            $address_code_id = $response_data['addressCodeId'];

            $quote->getShippingAddress()->setData(
                CornerDrop_Collect_Helper_Data::CORNERDROP_RESERVATION_CODE,
                $address_code_id
            );

            $quote->save();

            return static::RESULT_SUCCESS;
        } else if ($response->getCode() == CornerDrop_Collect_Helper_Api::STATUS_CODE_TERMINATE) {
            return static::RESULT_TERMINATE;
        }

        return static::RESULT_FAILED;
    }

    /**
     * Submit a new order to the CornerDrop API and, if successful, update the order
     * shipping address with a CornerDrop reference and notify the customer of their
     * tracking code.
     *
     * @param Mage_Sales_Model_Order $order
     * @param bool                   $save   Save the order model after setting the CornerDrop reference.
     * @param bool                   $notify Email the secret PIN to the customer.
     *
     * @return int
     */
    public function create($order, $save = true, $notify = true)
    {
        $orderData = $this->getOrderData($order);

        try {
            $response = Mage::getModel("cornerdrop_collect/api_action_orderCreate")->execute(
                $order->getQuoteId(),
                $order->getRemoteIp(),
                $orderData
            );
        } catch (Exception $e) {
            Mage::logException($e);

            return static::RESULT_FAILED;
        }

        if ($response->isSuccessful()) {
            $result = $response->getResult();

            if ($result["addressCodePublic"]) {
                // Set reference as the lastname and reset all other optional name fields
                $order->getShippingAddress()->addData(array(
                    "prefix"     => "",
                    "firstname"  => "Ref.",
                    "middlename" => "",
                    "lastname"   => $result["addressCodePublic"],
                    "suffix"     => ""
                ));

                if ($save) {
                    $order->save();
                }

                if ($notify && $result["secretPin"]) {
                    $store_id = $order->getStore()->getId();

                    try {
                        Mage::getModel("core/email_template")->sendTransactional(
                            Mage::helper("cornerdrop_collect/config")->getCodeEmailTemplate($store_id),
                            "sales",
                            $order->getCustomerEmail(),
                            $order->getCustomerName(),
                            array(
                                "order"          => $order,
                                "cornerdrop_ref" => $result["addressCodePublic"],
                                "cornerdrop_pin" => $result["secretPin"]
                            ),
                            $store_id
                        );
                    } catch (Exception $e) {
                        Mage::logException($e);

                        Mage::helper("cornerdrop_collect")->log(sprintf(
                            "Failed to notify the customer about the CornerDrop Code (Reference: %s, Code: %s): %s",
                            $result["addressCodePublic"],
                            $result["secretPin"],
                            $e->getMessage()
                        ));
                    }
                }
            }

            return static::RESULT_SUCCESS;
        } else if ($response->getCode() == CornerDrop_Collect_Helper_Api::STATUS_CODE_TERMINATE) {
            return static::RESULT_TERMINATE;
        }

        return static::RESULT_FAILED;
    }

    /**
     * Send an order dispatch notification to the CornerDrop API.
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return int
     */
    public function dispatch($order)
    {
        try {
            $response = Mage::getModel("cornerdrop_collect/api_action_orderDispatch")->execute(
                $order->getQuoteId(),
                $order->getRemoteIp(),
                array(
                    "externalOrderReference" => $order->getId()
                )
            );
        } catch (Exception $e) {
            Mage::logException($e);

            return static::RESULT_FAILED;
        }

        if ($response->isSuccessful()) {
            return static::RESULT_SUCCESS;
        } else if ($response->getCode() == CornerDrop_Collect_Helper_Api::STATUS_CODE_TERMINATE) {
            return static::RESULT_TERMINATE;
        }

        return static::RESULT_FAILED;
    }

    /**
     * Send a cancelled order notification to the CornerDrop API.
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return int
     */
    public function cancel($order)
    {
        try {
            $response = Mage::getModel("cornerdrop_collect/api_action_orderCancel")->execute(
                $order->getQuoteId(),
                $order->getRemoteIp(),
                array(
                    "externalOrderReference" => $order->getId(),
                    "cancellationReason" => "Not available"
                )
            );
        } catch (Exception $e) {
            Mage::logException($e);

            return static::RESULT_FAILED;
        }

        if ($response->isSuccessful()) {
            return static::RESULT_SUCCESS;
        } else if ($response->getCode() == CornerDrop_Collect_Helper_Api::STATUS_CODE_TERMINATE) {
            return static::RESULT_TERMINATE;
        }

        return static::RESULT_FAILED;
    }

    /**
     * Get an array of order data used by CornerDrop for the given order.
     *
     * @param Mage_Sales_Model_Order $order
     *
     * @return array
     */
    protected function getOrderData($order)
    {
        $address = $order->getBillingAddress();
        $shipping_address = $order->getShippingAddress();

        $cornerdrop_store_id = $shipping_address
            ->getData(CornerDrop_Collect_Helper_Data::CORNERDROP_STORE_ID_COLUMN);

        $cornerdrop_reservation_code = $shipping_address
            ->getData(CornerDrop_Collect_Helper_Data::CORNERDROP_RESERVATION_CODE);

        $data = array(
            "dropPointNodeId"        => $cornerdrop_store_id,
            "addressCodeId"          => $cornerdrop_reservation_code,
            "externalOrderReference" => $order->getId(),
            "prefix"                 => $order->getCustomerPrefix(),
            "firstName"              => $order->getCustomerFirstname(),
            "middleName"             => $order->getCustomerMiddlename(),
            "lastName"               => $order->getCustomerLastname(),
            "suffix"                 => $order->getCustomerSuffix(),
            "company"                => $address->getCompany(),
            "address1"               => $address->getStreet1(),
            "address2"               => $address->getStreet2(),
            "address3"               => $address->getStreet3(),
            "city"                   => $address->getCity(),
            "state"                  => $address->getRegion(),
            "country"                => $address->getCountry(),
            "postCode"               => $address->getPostcode(),
            "email"                  => $order->getCustomerEmail(),
            "phone"                  => $address->getTelephone(),
            "fax"                    => $address->getFax(),
            "vatNumber"              => $order->getCustomerTaxvat(),
            "orderDate"              => $order->getCreatedAtDate()->getIso(),
            "paymentMethod"          => $order->getPayment()->getMethod(),
            "subTotalAmount"         => $order->getSubtotal(),
            "discountAmount"         => $order->getDiscountAmount(),
            "taxAmount"              => $order->getTaxAmount(),
            "totalAmount"            => $order->getGrandTotal(),
            "shippingMethod"         => $order->getShippingMethod(),
            "shippingAmount"         => $order->getShippingAmount(),
            "couponCode"             => $order->getCouponCode(),
            "items"                  => array()
        );

        foreach ($order->getAllVisibleItems() as $item) {
            /** @var Mage_Sales_Model_Order_Item $item */
            $data["items"][] = array(
                "itemName"        => $item->getName(),
                "itemDescription" => $item->getDescription(),
                "itemSku"         => $item->getSku(),
                "itemPrice"       => $item->getPriceInclTax(),
                "itemWeight"      => $item->getWeight(),
                "itemQuantity"    => $item->getQtyOrdered(),
                "itemRowTotal"    => $item->getRowTotalInclTax()
            );
        }

        return $data;
    }
}
