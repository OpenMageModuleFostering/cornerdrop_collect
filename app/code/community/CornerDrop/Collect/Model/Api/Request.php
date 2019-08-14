<?php

class CornerDrop_Collect_Model_Api_Request extends Varien_Object
{
    protected $endpoint;

    protected $method;

    protected $headers;

    protected $responseModel;

    protected $client;

    /** @var CornerDrop_Collect_Helper_Data $_helper */
    protected $_helper;

    public function _construct()
    {
        parent::_construct();

        $this->method = Zend_Http_Client::GET;
        $this->headers = array();
        $this->responseModel = Mage::getModel('cornerdrop_collect/api_response');
        $this->_helper = Mage::helper('cornerdrop_collect');
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setEndpoint($url)
    {
        $this->endpoint = $url;

        return $this;
    }

    /**
     * @return string
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }

    /**
     * @param string $method
     * @return $this
     */
    public function setMethod($method)
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @param string $name
     * @param string $value
     * @return $this
     */
    public function setHeader($name, $value)
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }


    /**
     * @param CornerDrop_Collect_Model_Api_Response $response
     * @return $this
     */
    public function setResponseModel(CornerDrop_Collect_Model_Api_Response $response)
    {
        $this->responseModel = $response;

        return $this;
    }

    /**
     * @return CornerDrop_Collect_Model_Api_Response
     */
    public function getResponseModel()
    {
        return $this->responseModel;
    }

    public function setClient($client)
    {
        $this->client = $client;

        return $this;
    }

    public function getClient()
    {
        if (!$this->client) {
            $this->client = new Zend_Http_Client();
        }
        return $this->client;
    }

    /**
     * @return CornerDrop_Collect_Model_Api_Response
     * @throws Mage_Core_Exception
     * @throws Zend_Http_Client_Exception
     */
    public function send()
    {
        if (!$this->getEndpoint()) {
            // Can't make a request without a URL
            Mage::throwException("Unable to send a CornerDrop API request: No URL specified.");
        }

        $client = $this->getClient();

        $raw_request = $client
            ->setUri($this->getEndpoint())
            ->setMethod($this->getMethod())
            ->setHeaders($this->getHeaders());

        if ($this->getMethod() == Zend_Http_Client::GET) {
            $raw_request->setParameterGet($this->getData());
        }

        if ($this->getMethod() == Zend_Http_Client::POST) {
            $raw_request->setHeaders("Content-Type", "application/json");
            $raw_request->setRawData(json_encode($this->getData()));
        }

        $this->_helper->log(sprintf("API request:\n%s", $this->__toString()));

        try {
            $raw_response = $raw_request->request();
        } catch (Zend_Http_Client_Exception $e) {
            $this->_helper->log(sprintf('HTTP error: %s', $e->getMessage()));
            Mage::logException($e);
            return Mage::getModel('cornerdrop_collect/api_response_empty');
        }

        $response = $this->getResponseModel();
        $response->setRawResponse($raw_response);
        $this->_helper->log(sprintf(
            "API response:\n%s",
            $response->__toString()
        ), Zend_Log::DEBUG);

        return $response;
    }

    /**
     * Return the string representation of the API request.
     *
     * @return string
     */
    public function __toString()
    {
        $endpoint = $this->getEndpoint();
        if ($this->getMethod() == Zend_Http_Client::GET) {
            if ($params = $this->getData()) {
                array_walk($params, function (&$value, $key) {
                    $value = sprintf("%s=%s", $key, $value);
                });
                $endpoint = sprintf("%s?%s", $endpoint, implode("&", $params));
            }
        }

        $headers = $this->getHeaders();
        if (count($headers) > 0) {
            array_walk($headers, function (&$value, $key) {
                $value = ($value !== null && $value !== false) ? sprintf("%s: %s", $key, $value) : null;
            });
        }

        $body = "";
        if ($this->getMethod() == Zend_Http_Client::POST) {
            $body = json_encode($this->getData());
        }

        return sprintf("%s %s\n%s%s\n",
            $this->getMethod(),
            $endpoint,
            implode("\n", array_filter($headers)),
            ($body) ? sprintf("\n%s", $body) : ""
        );
    }
}
