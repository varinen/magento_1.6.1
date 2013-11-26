<?php

/**
 * @package 	Kiala_LocateAndSelect
 * @copyright   Copyright (c) 2012 Kiala
 * @author 		Phpro (http://www.phpro.be)
 */
class Kiala_LocateAndSelect_Block_Adminhtml_Orders extends Mage_Adminhtml_Block_Widget_Grid_Container
{

    public function __construct()
    {
        parent::__construct();

        $this->_blockGroup = 'locateandselect';
        $this->_controller = 'adminhtml_orders';
        $this->_headerText = Mage::helper('locateandselect')->__('Kiala Orders');

        $this->removeButton('add');
        if (Mage::helper('locateandselect')->isDesktopAppUser()) {
            $this->addButton('download', array(
                'label' =>  Mage::helper('locateandselect')->__('Download last exported file'),
                'onclick' => "setLocation('" . $this->getUrl('*/*/downloadLatestCsv') . "')"
            ));
        }
        if (Mage::helper('locateandselect')->isUpsWorldshipUser()) {
            $this->addButton('download', array(
                  'label' =>  Mage::helper('locateandselect')->__('Download last exported file'),
                  'onclick' => "setLocation('" . $this->getUrl('*/*/downloadLatestUpsCsv') . "')"
             ));
        }
    }
}
