<?php

/**
 * @package 	Kiala_LocateAndSelect
 * @copyright   Copyright (c) 2012 Kiala
 * @author 		Phpro (http://www.phpro.be)
 */
class Kiala_LocateAndSelect_Block_Adminhtml_Javascript extends Mage_Adminhtml_Block_Sales_Order_Abstract
{

    public function getOrder()
    {
        try
        {
            return parent::getOrder();
        } catch (Exception $e)
        {
            if (Mage::registry('current_shipment')) {
                $shipment = Mage::registry('current_shipment');
                $order = Mage::getModel('sales/order')->load($shipment->getOrderId());
                return $order;
            }
            if (Mage::registry('current_invoice')) {
                $invoice = Mage::registry('current_invoice');
                $order = Mage::getModel('sales/order')->load($invoice->getOrderId());
                return $order;
            }
        }
    }

}
