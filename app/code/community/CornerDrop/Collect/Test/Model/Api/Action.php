<?php

class CornerDrop_Collect_Test_Model_Api_Action extends CornerDrop_Collect_Test_Model_Api_Test_Case
{
    public function setUp()
    {
        parent::setUp();

        @session_start(); // Ignore header errors when attempting to start a session
    }

    /**
     * @loadFixture
     * @test
     */
    public function testExecute()
    {
        $response_model = Mage::getModel('cornerdrop_collect/api_response');
        $response_model
            ->setRawResponse(new Zend_Http_Response(200, array(), 'test response'));

        $request_model = $this->getModelMock('cornerdrop_collect/api_request', array('send'));
        $request_model
            ->expects($this->once())
            ->method('send')
            ->will($this->returnValue($response_model));

        $action = Mage::getModel('cornerdrop_collect/api_action_status');
        $action
            ->setApiIdentifier('test')
            ->setRequest($request_model)
            ->setResponse($response_model);

        // Ensure the response matches our expected response.
        $this->assertEquals($response_model, $action->execute(null, null));

        // Ensure the request method is correct
        $this->assertEquals('GET', $action->getRequest()->getMethod());

        // Ensure the endpoint is correct
        $this->assertEquals('https://testapi.cornerdrop.com/status', $action->getRequest()->getEndpoint());

        // Ensure the headers are correct
        $headers = array(
            'ApiKey' => null,
            'Identifier' => 'test',
            'SessionId' => null,
            'UserHostAddress' => null
        );
        $this->assertEquals($headers, $action->getRequest()->getHeaders());
    }
}
