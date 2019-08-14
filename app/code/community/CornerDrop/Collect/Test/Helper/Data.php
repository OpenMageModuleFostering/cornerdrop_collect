<?php

class CornerDrop_Collect_Test_Helper_Data extends EcomDev_PHPUnit_Test_Case
{

    /** @var CornerDrop_Collect_Helper_Data $helper */
    protected $helper;

    protected function setUp()
    {
        parent::setUp();

        $this->helper = Mage::helper("cornerdrop_collect");
    }

    protected function tearDown()
    {
        unset($this->helper);

        parent::tearDown();
    }

    public function testSearchReturnsResults()
    {
        $response_body = '{"result":{"results":[{"id":1,"displayName":"Test","latitude":51.320601,"longitude":-2.207436,"distance":7.6051834780367,"fullAddressHtml":"Test Street<br>Test City<br>Test County<br>AB1 1AB","address1":"Test Street","address2":"","address3":"","city":"Test City","county":"Test Region","country":"GB","postCode":"AB1 1AB","contactPhone":"123456789","openingHoursHtml":"Mon - Fri: 09:00 - 17:00<br>Sat: 09:00 - 17:00<br>","siteMarketing":"Test Description","availableSlots":5,"totalSlots":5}]},"code":2000,"message":"Success"}';
        $result = array(
            array(
                "id"               => 1,
                "name"             => "Test",
                "description"      => "Test Description",
                "address"          => array(
                    "vat_id"     => null,
                    "prefix"     => null,
                    "firstname"  => null,
                    "middlename" => null,
                    "lastname"   => null,
                    "suffix"     => null,
                    "company"    => "Test",
                    "street"     => array(
                        "Test Street",
                        "",
                        ""
                    ),
                    "city"       => "Test City",
                    "region"     => "Test Region",
                    "postcode"   => "AB1 1AB",
                    "country_id" => "GB",
                    "telephone"  => "123456789",
                    "fax"        => null,
                    "email"      => null
                ),
                "addressHtml"      => "Test Street<br>Test City<br>Test County<br>AB1 1AB",
                "location"         => array(
                    "distance"  => 7.6051834780367,
                    "latitude"  => 51.320601,
                    "longitude" => -2.207436
                ),
                "availability"     => array(
                    "available" => 5,
                    "total"     => 5
                ),
                "openingHoursHtml" => "Mon - Fri: 09:00 - 17:00<br>Sat: 09:00 - 17:00<br>"
            )
        );

        $api_action = $this->getModelMock("cornerdrop_collect/api_action_search", array("execute"));
        $api_action
            ->expects($this->once())
            ->method("execute")
            ->will($this->returnValue(
                Mage::getModel("cornerdrop_collect/api_response")
                    ->setRawResponse(new Zend_Http_Response(200, array(), $response_body))
            ));
        $this->replaceByMock("model", "cornerdrop_collect/api_action_search", $api_action);

        $this->assertEquals(
            $result,
            $this->helper->search(Mage::getModel("sales/quote"), "1", "1", "1"),
            "Failed asserting that search method returns results array on success."
        );
    }

    public function testSearchReturnsNullOnFailure()
    {
        $api_action = $this->getModelMock("cornerdrop_collect/api_action_search", array("execute"));
        $api_action
            ->expects($this->once())
            ->method("execute")
            ->will($this->returnValue(
                Mage::getModel("cornerdrop_collect/api_response")
                    ->setRawResponse(new Zend_Http_Response(500, array(), ""))
            ));
        $this->replaceByMock("model", "cornerdrop_collect/api_action_search", $api_action);

        $this->assertNull(
            $this->helper->search(Mage::getModel("sales/quote"), "1", "1", "1"),
            "Failed asserting that search method returns null on failure."
        );
    }

    public function testSearchReturnsNullOnError()
    {
        $api_action = $this->getModelMock("cornerdrop_collect/api_action_search", array("execute"));
        $api_action
            ->expects($this->once())
            ->method("execute")
            ->will($this->throwException(new Exception("Test exception")));
        $this->replaceByMock("model", "cornerdrop_collect/api_action_search", $api_action);

        $this->assertNull(
            $this->helper->search(Mage::getModel("sales/quote"), "1", "1", "1"),
            "Failed asserting that search method returns null on error."
        );
    }
}
