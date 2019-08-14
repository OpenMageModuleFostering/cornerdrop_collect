<?php

class CornerDrop_Collect_Test_Model_Api_Request extends CornerDrop_Collect_Test_Model_Api_Test_Case
{
    /**
     * @test
     * @expectedException Mage_Core_Exception
     */
    public function testSendNoEndpoint()
    {
        $model = $this->getModelMock('cornerdrop_collect/api_request', array('getClient'));
        $model
            ->expects($this->never())
            ->method('getClient');

        $model->send();
    }

    /**
     * @test
     */
    public function testSendNoResponse()
    {
        $http_client = $this->getMock('Zend_Http_Client', array('request'));
        $http_client
            ->expects($this->once())
            ->method('request')
            ->will($this->throwException(new Zend_Http_Client_Exception('Test exception')));

        $model = Mage::getModel('cornerdrop_collect/api_request');
        $model
            ->setClient($http_client)
            ->setEndpoint('http://test.cornerdrop.com/');

        $this->assertInstanceOf('CornerDrop_Collect_Model_Api_Response_Empty', $model->send());
    }

    /**
     * @test
     */
    public function testSendValidResponse()
    {
        $test_raw_response = new Zend_Http_Response(200, array());

        $http_client = $this->getMock('Zend_Http_Client', array('request'));
        $http_client
            ->expects($this->once())
            ->method('request')
            ->will($this->returnValue($test_raw_response));

        $response_model = $this->getModelMock('cornerdrop_collect/api_response', array('getRawResponse'));
        $response_model
            ->method('getRawResponse')
            ->will($this->returnValue($test_raw_response));

        $request_model = Mage::getModel('cornerdrop_collect/api_request');
        $request_model
            ->setClient($http_client)
            ->setEndpoint('http://test.cornerdrop.com/')
            ->setResponseModel($response_model);

        $this->assertInstanceOf('CornerDrop_Collect_Model_Api_Response', $request_model->send());
    }
}
