<?php

class Kiala_LocateAndSelect_Model_Mysql4_Language_Collection extends Varien_Data_Collection_Db
{
    protected $_languageTable;
 
    public function __construct()
    {
        $resources = Mage::getSingleton('core/resource');
        parent::__construct($resources->getConnection('kiala_locateandselect_read'));
        $this->_languageTable= $resources->getTableName('locateandselect/language');
 
        $this->_select->from(
        		array('language'=>$this->_languageTable),
 		       	array('*')
        		);
        $this->setItemObjectClass(Mage::getConfig()->getModelClassName('locateandselect/language'));
    }
}