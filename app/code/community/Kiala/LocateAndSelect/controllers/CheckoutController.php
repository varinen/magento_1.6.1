<?php

/**
 * @package 	Kiala_LocateAndSelect
 * @copyright   Copyright (c) 2012 Kiala
 * @author 		Phpro (http://www.phpro.be)
 */
class Kiala_LocateAndSelect_CheckoutController extends Mage_Core_Controller_Front_Action
{
    
    public function chooselanguageAction() {
        $returnArray = array();
        
        $this->loadLayout();
        
        $returnArray['html'] = $this->getLayout()
            ->createBlock('locateandselect/checkout_onepage_shipping_method_kiala_language')
            ->setTemplate('kiala/locateandselect/checkout/onepage/shipping_method/kiala/language.phtml')
            ->toHtml();
        
        $this->getResponse()->setBody(Zend_Json::encode($returnArray));
    }

    public function savelanguageAction()
    {
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $quote->setLanguage($this->getRequest()->getParam('language'))->save();
    }

}
