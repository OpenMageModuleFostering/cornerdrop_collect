<?php

/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;
$installer->startSetup();

// Add is_cornerdrop_collect attribute to customer address

$admin_store = Mage::app()->getStore(Mage_Core_Model_App::ADMIN_STORE_ID);

$attribute = Mage::getSingleton('eav/config')
    ->getAttribute('customer_address', CornerDrop_Collect_Helper_Data::IS_CORNERDROP_COLLECT);
$attribute->setWebsite($admin_store->getWebsite());
if (!$attribute->getId()) {
    $attribute->addData(array(
        'label'           => 'Is CornerDrop Collect',
        'backend_type'    => 'int',
        'frontend_input'  => 'hidden',
        'is_user_defined' => 0,
        'is_system'       => 1,
        'is_visible'      => 1,
        'is_required'     => 0,
        'used_in_forms'   => array(
            'customer_address_edit'
        )
    ));
    $attribute->save();
}

// Add cornerdrop_store_id attribute to customer address

$attribute = Mage::getSingleton('eav/config')
    ->getAttribute('customer_address', CornerDrop_Collect_Helper_Data::CORNERDROP_STORE_ID_COLUMN);
$attribute->setWebsite($admin_store->getWebsite());
if (!$attribute->getId()) {
    $attribute->addData(array(
        'label'           => 'CornerDrop Store ID',
        'backend_type'    => 'int',
        'frontend_input'  => 'hidden',
        'is_user_defined' => 0,
        'is_system'       => 1,
        'is_visible'      => 1,
        'is_required'     => 0,
        'used_in_forms'   => array(
            'customer_address_edit'
        )
    ));
    $attribute->save();
}

// Add CornerDrop Collect tax class

/** @var Mage_Tax_Model_Class $model */
$model = Mage::getModel('tax/class');
/** @var CornerDrop_Collect_Helper_Data $helper */
$helper = Mage::helper('cornerdrop_collect');

try {
    $model->setData(array(
        'class_name' => $helper->getCornerDropFeeLabel(),
        'class_type' => Mage_Tax_Model_Class::TAX_CLASS_TYPE_PRODUCT
    ));
    $model->save();

    /**
     * Set CornerDrop Tax Class to default value.
     */
    Mage::getModel('core/config')->saveConfig(CornerDrop_Collect_Helper_Config::XML_PATH_FEE_SETTINGS_TAX_CLASS, $model->getId());

} catch (Exception $e) {
    $helper->log($e->getMessage());
}

$installer->endSetup();
