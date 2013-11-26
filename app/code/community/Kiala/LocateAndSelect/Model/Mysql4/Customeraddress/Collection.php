<?php

class Kiala_LocateAndSelect_Model_Mysql4_CustomerAddress_Collection extends Varien_Data_Collection_Db
{
    protected $_customerAddressTable;
 
    public function __construct()
    {
        $resources = Mage::getSingleton('core/resource');
        parent::__construct($resources->getConnection('kiala_locateandselect_read'));
        $this->_customerAddressTable= $resources->getTableName('locateandselect/customeraddress');
 
        $this->_select->from(
        		array('customeraddress'=>$this->_customerAddressTable),
 		       	array('*')
        		);
        $this->setItemObjectClass(Mage::getConfig()->getModelClassName('locateandselect/customeraddress'));
    }
}