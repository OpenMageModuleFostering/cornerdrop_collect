<?php

class CornerDrop_Collect_Test_Config_Base extends EcomDev_PHPUnit_Test_Case_Config {

    /**
     * @test
     */
    public function testClassAlias() {
        $this->assertHelperAlias('cornerdrop_collect/test', 'CornerDrop_Collect_Helper_Test');
        $this->assertBlockAlias('cornerdrop_collect/test', 'CornerDrop_Collect_Block_Test');
        $this->assertModelAlias('cornerdrop_collect/test', 'CornerDrop_Collect_Model_Test');
    }

}
