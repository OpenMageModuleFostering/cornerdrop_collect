<?php

class CornerDrop_Collect_Test_Model_Api_Response extends CornerDrop_Collect_Test_Model_Api_Test_Case
{
    /**
     * @test
     * @dataProvider dataProvider
     * @dataProviderFile response_testIsSuccessful.yaml
     */
    public function testIsSuccessful($response_code, $response_data_file, $is_successful)
    {
        $response_body = ($response_data_file) ? $this->getDataFileContents($response_data_file) : "";
        $http_response = new Zend_Http_Response($response_code, array(), $response_body);
        $model = Mage::getModel('cornerdrop_collect/api_response');
        $model->setRawResponse($http_response);

        $this->assertEquals($is_successful, $model->isSuccessful());
    }
}
