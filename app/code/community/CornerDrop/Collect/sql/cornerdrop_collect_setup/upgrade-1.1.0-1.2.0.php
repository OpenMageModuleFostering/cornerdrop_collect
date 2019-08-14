<?php

/** @var Mage_Core_Model_Resource_Setup $installer */
$installer = $this;

$installer->startSetup();

$connection = $installer->getConnection();

// Convert all order notification columns to int

$table = $installer->getTable("sales/order");

$notification_columns = array(
    CornerDrop_Collect_Helper_Data::ORDERED_NOTIFICATION_COLUMN,
    CornerDrop_Collect_Helper_Data::SHIPPED_NOTIFICATION_COLUMN,
    CornerDrop_Collect_Helper_Data::CANCELLED_NOTIFICATION_COLUMN
);

$type = array(
    "type"     => Varien_Db_Ddl_Table::TYPE_INTEGER,
    "unsigned" => true,
    "nullable" => false,
    "default"  => 0,
    "comment"  => "CornerDrop Collect Column"
);

foreach ($notification_columns as $column) {
    $connection->modifyColumn(
        $table,
        $column,
        $type
    );
}

$installer->endSetup();
