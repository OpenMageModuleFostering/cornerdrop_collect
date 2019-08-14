<?php

class CornerDrop_Collect_Adminhtml_Cornerdrop_Collect_SearchController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $query = $this->getRequest()->getParam("q");
        $lat = $this->getRequest()->getParam("lat");
        $long = $this->getRequest()->getParam("long");

        if (is_null($lat) || is_null($long)) {
            return $this->setErrorResponse(400, $this->getHelper()->__("Missing parameters."));
        }

        /** @var Mage_Sales_Model_Quote $quote */
        $quote = Mage::getSingleton('adminhtml/session_quote')->getQuote();

        $quote->setRemoteIp(Mage::helper('core/http')->getRemoteAddr());

        $results = $this->getHelper()->search(
            $quote,
            $query,
            $lat,
            $long
        );

        if (is_null($results)) {
            return $this->setErrorResponse(500, $this->getHelper()->__("Failed to fetch the search results, please try again."));
        }

        $this->getResponse()
            ->setHeader("Content-type", "application/json")
            ->appendBody(Mage::helper("core")->jsonEncode($results));

        return $this;
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

    /**
     * Set an error code and message on the response.
     *
     * @param int    $code
     * @param string $message
     *
     * @return $this
     * @throws Zend_Controller_Response_Exception
     */
    protected function setErrorResponse($code, $message)
    {
        $this->getResponse()
            ->setHttpResponseCode($code)
            ->setHeader("Content-type", "application/json")
            ->appendBody(Mage::helper("core")->jsonEncode(array(
                "message" => $message
            )));

        return $this;
    }
}
