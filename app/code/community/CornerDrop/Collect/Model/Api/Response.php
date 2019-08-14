<?php

/**
 * Class CornerDrop_Collect_Model_Api_Response
 *
 * @method int getCode()
 */
class CornerDrop_Collect_Model_Api_Response extends Varien_Object
{
    /**
     * @var Zend_Http_Response
     */
    protected $rawResponse;

    /**
     * @var bool
     */
    protected $successful;

    /**
     * @var array
     */
    protected $headers;

    /** @var CornerDrop_Collect_Helper_Data $_helper */
    protected $_helper;

    public function _construct()
    {
        parent::_construct();
        $this->_helper = Mage::helper('cornerdrop_collect');
        $this->successful = false;
    }

    /**
     * Set response object
     *
     * @param Zend_Http_Response $response
     * @return $this
     */
    public function setRawResponse(Zend_Http_Response $response)
    {
        $this->rawResponse = $response;

        $this->parseRawResponse($response);

        return $this;
    }

    public function getRawResponse()
    {
        return $this->rawResponse;
    }

    /**
     * Bool check if the response was successful
     *
     * @return bool
     */
    public function isSuccessful()
    {
        return $this->successful;
    }

    /**
     * Set Response Headers
     *
     * @param array $headers
     * @return $this
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;

        return $this;
    }

    /**
     * Return the response headers as an array.
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Process the response and handle errors
     *
     * @param Zend_Http_Response $response
     * @return $this
     */
    protected function parseRawResponse(Zend_Http_Response $response)
    {
        $this->successful = false;

        $this->setHeaders($response->getHeaders());

        $json = json_decode($response->getBody(), true);
        if (is_array($json)) {
            $this->setData($json);
        } else {
            $this->setData('message', 'Response body is empty');
        }

        if ($response->isSuccessful()) {
            if ($this->getCode() == CornerDrop_Collect_Helper_Api::STATUS_CODE_SUCCESS) {
                $this->successful = true;
            }
        } else {
            $this->_helper->log(
                sprintf(
                    "Unsuccessful HTTP response: %s %s",
                    $response->getStatus(),
                    $response->responseCodeAsText($response->getStatus())
                ),
                Zend_Log::ERR
            );
        }

        return $this;
    }

    /**
     * Return the raw response as a string.
     *
     * @return string
     */
    public function __toString()
    {
        return sprintf("%s\n%s",
            $this->getRawResponse()->getHeadersAsString(true, "\n"),
            $this->getRawResponse()->getBody()
        );
    }

}
