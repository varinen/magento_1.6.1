<?php

class Kiala_LocateAndSelect_Model_Order_Shipment_Track extends Mage_Sales_Model_Order_Shipment_Track
{

    public function getNumberDetail()
    {

        if ($this->getCarrierCode() != 'kiala') {
            return parent::getNumberDetail();
        }

        $carrierInstance = Mage::getSingleton('shipping/config')->getCarrierInstance($this->getCarrierCode());
        if (!$carrierInstance) {
            $custom['title'] = $this->getTitle();
            $custom['number'] = $this->getNumber();
            return $custom;
        } else {
            $carrierInstance->setStore($this->getStore());
        }

        if (!$trackingInfo = $carrierInstance->getTrackingInfo($this->getNumber(), $this->getShipment()->getShippingAddress()->getCountry(), $this->getShipment()->getOrder())) {
            return Mage::helper('sales')->__('No detail for number "%s"', $this->getNumber());
        }

        return $trackingInfo;
    }

}
