<?php

class CornerDrop_Collect_Model_Cron extends Varien_Object
{
    const STATUS_NOT_NOTIFIED = 0;
    const STATUS_NOTIFICATION_SUCCESSFUL = 1;
    const STATUS_NOTIFICATION_TERMINATED = 99;

    /**
     * Send all pending order notifications to the CornerDrop API.
     */
    public function sendNotifications()
    {
        $this->sendNewOrderNotifications();
        $this->sendShippedOrderNotifications();
        $this->sendCancelledOrderNotifications();
    }

    /**
     * Send notifications to CornerDrop API for all new CornerDrop orders.
     */
    public function sendNewOrderNotifications()
    {
        $collection = $this->getOrderHelper()->getCornerDropOrderCollection()
            ->addFieldToFilter(
                CornerDrop_Collect_Helper_Data::ORDERED_NOTIFICATION_COLUMN,
                array("eq" => static::STATUS_NOT_NOTIFIED)
            );

        foreach ($collection as $order) {
            $status = null;
            switch ($this->getOrderHelper()->create($order, false)) {
                case CornerDrop_Collect_Helper_Order::RESULT_SUCCESS:
                    $status = static::STATUS_NOTIFICATION_SUCCESSFUL;
                    break;
                case CornerDrop_Collect_Helper_Order::RESULT_TERMINATE:
                    $status = static::STATUS_NOTIFICATION_TERMINATED;
                    break;
            }

            if ($status !== null) {
                $order
                    ->setData(CornerDrop_Collect_Helper_Data::ORDERED_NOTIFICATION_COLUMN, $status)
                    ->save();
            }
        }
    }

    /**
     * Send notifications to CornerDrop API for all dispatched CornerDrop orders.
     */
    public function sendShippedOrderNotifications()
    {
        $collection = $this->getOrderHelper()->getCornerDropOrderCollection();
        $collection
            ->addFieldToFilter(
                CornerDrop_Collect_Helper_Data::SHIPPED_NOTIFICATION_COLUMN,
                array("eq" => static::STATUS_NOT_NOTIFIED)
            )
            ->join(
                array("shipment" => "sales/shipment"),
                "shipment.order_id = main_table.entity_id",
                null
            )
            ->getSelect()->group("main_table.entity_id");

        foreach ($collection as $order) {
            $status = null;
            switch ($this->getOrderHelper()->dispatch($order)) {
                case CornerDrop_Collect_Helper_Order::RESULT_SUCCESS:
                    $status = static::STATUS_NOTIFICATION_SUCCESSFUL;
                    break;
                case CornerDrop_Collect_Helper_Order::RESULT_TERMINATE:
                    $status = static::STATUS_NOTIFICATION_TERMINATED;
                    break;
            }

            if ($status !== null) {
                $order
                    ->setData(CornerDrop_Collect_Helper_Data::SHIPPED_NOTIFICATION_COLUMN, $status)
                    ->save();
            }
        }
    }

    /**
     * Send notifications to CornerDrop API for all cancelled CornerDrop orders.
     */
    public function sendCancelledOrderNotifications()
    {
        $collection = $this->getOrderHelper()->getCornerDropOrderCollection()
            ->addFieldToFilter("state", Mage_Sales_Model_Order::STATE_CANCELED)
            ->addFieldToFilter(
                CornerDrop_Collect_Helper_Data::CANCELLED_NOTIFICATION_COLUMN,
                array("eq" => static::STATUS_NOT_NOTIFIED)
            );

        foreach ($collection as $order) {
            $status = null;
            switch ($this->getOrderHelper()->cancel($order)) {
                case CornerDrop_Collect_Helper_Order::RESULT_SUCCESS:
                    $status = static::STATUS_NOTIFICATION_SUCCESSFUL;
                    break;
                case CornerDrop_Collect_Helper_Order::RESULT_TERMINATE:
                    $status = static::STATUS_NOTIFICATION_TERMINATED;
                    break;
            }

            if ($status !== null) {
                $order
                    ->setData(CornerDrop_Collect_Helper_Data::CANCELLED_NOTIFICATION_COLUMN, $status)
                    ->save();
            }
        }
    }

    /**
     * Get the order helper.
     *
     * @return CornerDrop_Collect_Helper_Order
     */
    protected function getOrderHelper()
    {
        return Mage::helper("cornerdrop_collect/order");
    }
}
