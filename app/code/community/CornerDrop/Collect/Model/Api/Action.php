<?php

class CornerDrop_Collect_Model_Api_Action extends Varien_Object
{
    const ENDPOINT = "";
    const METHOD = "GET";

    const DEFAULT_REQUEST_MODEL = 'cornerdrop_collect/api_request';
    const DEFAULT_RESPONSE_MODEL = 'cornerdrop_collect/api_response';

    /** @var CornerDrop_Collect_Model_Api_Request $request */
    protected $request;

    /** @var CornerDrop_Collect_Model_Api_Response $response */
    protected $response;

    /** @var Mage_Core_Model_Store|int|null $store */
    protected $store;

    /** @var CornerDrop_Collect_Helper_Api */
    protected $apiHelper;

    protected $apiIdentifier;

    public function __construct()
    {
        parent::__construct();

        $this->apiHelper = Mage::helper('cornerdrop_collect/api');
    }

    /**
     * Set the request model to use for the API action
     *
     * @param CornerDrop_Collect_Model_Api_Request $request
     * @return $this
     */
    public function setRequest(CornerDrop_Collect_Model_Api_Request $request)
    {
        $this->request = $request;

        return $this;
    }

    /**
     * Returns the request model used for this API action
     *
     * @return CornerDrop_Collect_Model_Api_Request
     */
    public function getRequest()
    {
        if (!$this->request) {
            $this->request = Mage::getModel(static::DEFAULT_REQUEST_MODEL);
        }

        return $this->request;
    }

    /**
     * Set the response model to use for the API action
     *
     * @param CornerDrop_Collect_Model_Api_Response $response
     * @return $this
     */
    public function setResponse(CornerDrop_Collect_Model_Api_Response $response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * Returns the response model used for this API action
     *
     * @return CornerDrop_Collect_Model_Api_Response
     */
    public function getResponse()
    {
        if (!$this->response) {
            $this->response = Mage::getModel(static::DEFAULT_RESPONSE_MODEL);
        }

        return $this->response;
    }

    /**
     * Set the store to use for the API action
     *
     * @param Mage_Core_Model_Store|int|null $store
     * @return $this
     */
    public function setStore($store)
    {
        $this->store = $store;

        return $this;
    }

    /**
     * Return a store used for the API action
     *
     * @return int|Mage_Core_Model_Store
     */
    public function getStore()
    {
        if (!$this->store) {
            $this->store = Mage::app()->getStore();
        }

        return $this->store;
    }

    /**
     * Execute the API call, and return the response
     * $session_id = quote id
     * $user_host_address = remote ip from quote.
     *
     * @param string $session_id
     * @param string $user_host_address
     * @param array $parameters
     * @return CornerDrop_Collect_Model_Api_Response
     */
    public function execute($session_id, $user_host_address, $parameters = array())
    {
        $request = $this->getRequest();
        $store = $this->getStore();
        $api_key = Mage::helper('cornerdrop_collect/config')->getApiKey($store);

        $request
            ->setResponseModel($this->getResponse())
            ->setEndpoint($this->apiHelper->buildEndpoint(static::ENDPOINT, $store))
            ->setMethod(static::METHOD)
            ->setHeader('ApiKey', $api_key)
            ->setHeader('Identifier', $this->getApiIdentifier())
            ->setHeader('SessionId', $session_id)
            ->setHeader('UserHostAddress', $user_host_address)
            ->setData($parameters);

        return $request->send();
    }

    public function getApiIdentifier()
    {
        if (!$this->apiIdentifier) {
            $this->apiIdentifier = $this->apiHelper->getApiIdentifier();
        }

        return $this->apiIdentifier;
    }

    public function setApiIdentifier($api_identifier)
    {
        $this->apiIdentifier = $api_identifier;

        return $this;
    }
}
