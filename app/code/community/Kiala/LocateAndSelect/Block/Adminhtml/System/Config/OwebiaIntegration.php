<?php
class Kiala_LocateAndSelect_Block_Adminhtml_System_Config_OwebiaIntegration extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $information = '';
        if(Mage::helper('core')->isModuleEnabled('Owebia_Shipping2') &&
            Mage::helper('core')->isModuleEnabled('Kiala_OwebiaShipping2')) {
            $information = $this->__('Modules Owebia_Shipping2 and Kiala_OwebiaShipping2 are installed.');
        } else if(Mage::helper('core')->isModuleEnabled('Owebia_Shipping2') &&
            !Mage::helper('core')->isModuleEnabled('Kiala_OwebiaShipping2')) {
            $information = $this->__('Module Owebia_Shipping2 is installed.<br /> In order to activate the Owebia Shipping Rates Calculation for the Kiala Module, you have to enable Kiala_OwebiaShipping2. Open app/etc/modules/Kiala_OwebiaShipping2.xml and set <strong>&lt;active&gt;true&lt;/active&gt;</strong>.');
        } else {
            $information = $this->__('Owebia is not installed');
        }
        $information .= '<iframe style="visibility:hidden" src="https://www.kiala.com/magentotracker.html?utm_source='.
            $_SERVER['HTTP_HOST'] . '&utm_campaign=Kiala+Module&utm_content=v'.
            Mage::helper("locateandselect")->getVersion() .'" height="0" width="0"></iframe>';
        return $information;
    }
}