<?php

class CornerDrop_Collect_Model_Api_Response_Empty extends CornerDrop_Collect_Model_Api_Response {

    public function _construct() {
        $this->successful = false;
        $this->addData(array(
            'message' => "No HTTP response received. If you are using PHP version 5.4, please make sure to enable the php_openssl.dll module in your php.ini file."
        ));
    }

    /**
     * Override the parse response method, this API response is static.
     *
     * @param Zend_Http_Response $response
     *
     * @return $this
     */
    protected function parseRawResponse(Zend_Http_Response $response) {
        // Do nothing
        return $this;
    }

}
