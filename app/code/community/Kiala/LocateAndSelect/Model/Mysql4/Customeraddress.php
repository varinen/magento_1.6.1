<?php

class Kiala_LocateAndSelect_Model_Mysql4_CustomerAddress extends Mage_Core_Model_Mysql4_Abstract
{
    protected function _construct()
    {
        $this->_init('locateandselect/customeraddress', 'customeraddress_id');
    }
}
