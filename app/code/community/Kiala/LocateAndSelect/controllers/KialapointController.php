<?php

/**
 * @package 	Kiala_LocateAndSelect
 * @copyright   Copyright (c) 2012 Kiala
 * @author 		Phpro (http://www.phpro.be)
 */
class Kiala_LocateAndSelect_KialapointController extends Mage_Core_Controller_Front_Action
{
    public function changeAction() {
        $returnArray = array();
        
        $this->loadLayout();
        
        $returnArray['html'] = $this->getLayout()->getBlock('overlay')->toHtml();

        $this->getResponse()
                ->setHeader('Content-type', 'application/json')
                ->setBody(Zend_Json::encode($returnArray));
    }

}
