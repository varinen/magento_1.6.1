<?php

class Kiala_LocateAndSelect_Block_Adminhtml_System_Config_LocateAndSelect extends Mage_Adminhtml_Block_System_Config_Form_Field {
    
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        return $this->_toHtml();
    }
    
    protected function _prepareLayout()
    {
        $button = $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setData(array(
                'label'     => Mage::helper('locateandselect')->__('Add Parameter'),
                'onclick'   => 'return lnsControl.addItem()',
                'class'     => 'add'
            ));
        $button->setName('add_parameter_button');

        $this->setChild('add_button', $button);
        if (!$this->getRequest()->getParam('popup')) {
            $this->setChild('back_button',
                $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setData(array(
                        'label'     => Mage::helper('locateandselect')->__('Back'),
                        'onclick'   => 'setLocation(\''.$this->getUrl('*/*/', array('store'=>$this->getRequest()->getParam('store', 0))).'\')',
                        'class' => 'back'
                    ))
            );
        } else {
            $this->setChild('back_button',
                $this->getLayout()->createBlock('adminhtml/widget_button')
                    ->setData(array(
                        'label'     => Mage::helper('locateandselect')->__('Close Window'),
                        'onclick'   => 'window.close()',
                        'class' => 'cancel'
                    ))
            );
        }

        parent::_prepareLayout();
        
        if (!$this->getTemplate()) {
            $this->setTemplate('kiala/locateandselect/system/config/locateandselect.phtml');
        }
        
        return $this;
    }    
    
    public function getAddButtonHtml()
    {
        return $this->getChildHtml('add_button');
    }
    
    public function getDeleteButtonHtml()
    {
        return $this->getChildHtml('delete_button');
    }
    
    public function getBackButtonHtml()
    {
        return $this->getChildHtml('back_button');
    }
    
    public function getValues() {
        return unserialize(Mage::getStoreConfig('carriers/kiala/frontend_url_parameters'));
    }
}