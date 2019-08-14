<?php

abstract class CornerDrop_Collect_Test_Model_Api_Test_Case extends EcomDev_PHPUnit_Test_Case
{
    protected function getDataFileContents($file) {
        $directory_tree = array(
            Mage::getModuleDir('', 'CornerDrop_Collect'),
            'Test',
            'Model',
            'Api',
            'data',
            $file
        );

        $file_path = join(DS, $directory_tree);

        return file_get_contents($file_path);
    }
}
