<?php
class Kiala_LocateAndSelect_Block_Sales_Order_Info extends Mage_Sales_Block_Order_Info {
    protected function _construct()
    {
        parent::_construct();
        if (file_exists('LICENSE_EE.txt') || file_exists('LICENSE_PE.txt')) {
            $this->setTemplate('kiala/locateandselect/sales/order/info-ee.phtml');
        } else {
            $this->setTemplate('kiala/locateandselect/sales/order/info-ce.phtml');
        }
    }
}