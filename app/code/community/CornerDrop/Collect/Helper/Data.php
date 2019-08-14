<?php

class CornerDrop_Collect_Helper_Data extends Mage_Core_Helper_Abstract
{

    const CORNERDROP_FEE_AMOUNT = 'cornerdrop_fee_amount';
    const BASE_CORNERDROP_FEE_AMOUNT = 'base_cornerdrop_fee_amount';
    const CORNERDROP_TAX = 'cornerdrop_tax_amount';
    const BASE_CORNERDROP_TAX = 'base_cornerdrop_tax_amount';
    const ORDERED_NOTIFICATION_COLUMN = 'cornerdrop_notification_ordered';
    const SHIPPED_NOTIFICATION_COLUMN = 'cornerdrop_notification_shipped';
    const CANCELLED_NOTIFICATION_COLUMN = 'cornerdrop_notification_cancelled';

    const IS_CORNERDROP_COLLECT = 'is_cornerdrop_collect';
    const CORNERDROP_STORE_ID_COLUMN = 'cornerdrop_store_id';
    const CORNERDROP_FEE_CODE = 'cornerdrop_fee';
    const CORNERDROP_RESERVATION_CODE = 'cornerdrop_reservation_code';

    /**
     * Log a message to a log file.
     *
     * @param     $message
     * @param int $level
     */
    public function log($message, $level = Zend_Log::DEBUG)
    {
        Mage::log($message, $level, 'cornerdrop.log');
    }

    /**
     * Check if the given address is a CornerDrop Collect address.
     *
     * @param Mage_Sales_Model_Quote_Address|Mage_Sales_Model_Order_Address $address
     *
     * @return bool
     */
    public function isCornerDropAddress($address) {
        return $address->getData(static::IS_CORNERDROP_COLLECT) == 1;
    }

    /**
     * Check if the quote is using CornerDrop
     *
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return bool
     */
    public function isQuoteCornerDrop(Mage_Sales_Model_Quote $quote)
    {
        return !$quote->isVirtual() && $this->isCornerDropAddress($quote->getShippingAddress());
    }

    /**
     * Check if the order is using CornerDrop, the check can be done by the Invoice and Creditmemo blocks too.
     *
     * @param Mage_Sales_Model_Order|Mage_Sales_Model_Order_Invoice|Mage_Sales_Model_Order_Creditmemo $source
     *
     * @return bool
     */
    public function isOrderCornerDrop($source)
    {
        return !$source->getIsVirtual() && $this->isCornerDropAddress($source->getShippingAddress());
    }

    /**
     * Get the Label for CornerDrop
     *
     * @return string
     */
    public function getCornerDropFeeLabel()
    {
        return $this->__('CornerDrop Fee');
    }

    /**
     * Get the HTML to print a CornerDrop logo for the current theme.
     *
     * @return string
     */
    public function getLogoHtml()
    {
        $sources = array(
            "1x" => "cornerdrop/collect/images/logo_125.jpg",
            "2x" => "cornerdrop/collect/images/logo_250.jpg",
            "4x" => "cornerdrop/collect/images/logo_500.jpg"
        );

        return sprintf('<img src="%s" srcset="%s" width="125" alt="CornerDrop" class="cdc-logo" />',
            Mage::getDesign()->getSkinUrl($sources["1x"]),
            implode(",", array_map(function ($path, $cond) {
                return sprintf("%s %s", Mage::getDesign()->getSkinUrl($path), $cond);
            }, $sources, array_keys($sources)))
        );
    }

    /**
     * Perform a search for CornerDrop Collect locations using the API
     * and return the results as an array. Returns null if the search
     * failed.
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param string $query
     * @param string $lat
     * @param string $long
     *
     * @return array|null
     */
    public function search($quote, $query, $lat, $long)
    {
        try {
            $response = Mage::getModel("cornerdrop_collect/api_action_search")->execute(
                $quote->getId(),
                $quote->getRemoteIp(),
                array(
                    "searchString" => $query,
                    "latitude" => $lat,
                    "longitude" => $long
                )
            );
        } catch (Exception $e) {
            Mage::logException($e);

            return null;
        }

        if ($response->isSuccessful()) {
            $results = array();

            $data = $response->getResult();

            if ($data && array_key_exists("results", $data) && is_array($data["results"])) {
                $default_address = $this->getCustomerAddress($quote);

                foreach ($data["results"] as $result) {
                    $results[] = $this->buildResult($result, $default_address);
                }
            }

            return $results;
        }

        return null;
    }

    /**
     * Get the customer billing address fields as an array from the given quote.
     *
     * @param Mage_Sales_Model_Quote $quote
     *
     * @return array
     */
    protected function getCustomerAddress($quote)
    {
        $address = $quote->getBillingAddress()->exportCustomerAddress()->toArray();

        // Remove internal VAT fields
        unset($address["vat_is_valid"]);
        unset($address["vat_request_id"]);
        unset($address["vat_request_date"]);
        unset($address["vat_request_success"]);

        unset($address["region_id"]);

        return $address;
    }

    /**
     * Build a result array out of the raw data returned by the API.
     *
     * @param array $data
     * @param array $default_address
     *
     * @return array
     */
    protected function buildResult($data, $default_address) {
        $result = array(
            "id"               => $data["id"],
            "name"             => $data["displayName"],
            "description"      => $data["siteMarketing"],
            "address"          => $default_address,
            "addressHtml"      => $data["fullAddressHtml"],
            "location"         => array(
                "distance"  => $data["distance"],
                "latitude"  => $data["latitude"],
                "longitude" => $data["longitude"]
            ),
            "availability"     => array(
                "available" => $data["availableSlots"],
                "total"     => $data["totalSlots"]
            ),
            "openingHoursHtml" => $data["openingHoursHtml"]
        );

        foreach (array(
            "displayName"  => "company",
            "city"         => "city",
            "contactPhone" => "telephone",
            "countryCode"  => "country_id",
            "county"       => "region",
            "postCode"     => "postcode"
        ) as $source => $destination) {
            $result["address"][$destination] = $data[$source];
        }

        $result["address"]["firstname"] = "CornerDrop";
        $result["address"]["lastname"] = "code";
        $result["address"]["street"] = array($data["address1"], $data["address2"], $data["address3"]);

        return $result;
    }

}
