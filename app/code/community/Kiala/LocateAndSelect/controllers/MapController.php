<?php

/**
 * @package 	Kiala_LocateAndSelect
 * @copyright   Copyright (c) 2012 Kiala
 * @author 		Phpro (http://www.phpro.be)
 */
class Kiala_LocateAndSelect_MapController extends Mage_Core_Controller_Front_Action
{

    /**
     * Triggered when selecting a kialapoint on the map
     */
    public function selectonmapAction()
    {
        if($this->_saveKialapointDetails()) {
            echo '<script>window.parent.updateKialapoint()</script>';
        } else {
            echo '<script>window.parent.showKialaSelectError()</script>';
        }
    }
    
    /**
     * Triggered when selecting a kialapoint in checkout
     */
    public function selectonfrontendAction()
    {
        $returnArray = array();
        
        $returnArray['success'] = $this->_saveKialapointDetails();
        
        $this->getResponse()
                ->setHeader('Content-type', 'application/json')
                ->setBody(Zend_Json::encode($returnArray));
    }
    
    /**
     * Save kialapoint details in session and database
     * @return boolean
     */
    protected function _saveKialapointDetails()
    {
        if ($this->getRequest()->isGet() && $this->getRequest()->getParam("shortkpid") != '') {
            $shortkpid = $this->getRequest()->getParam('shortkpid');
            $locateAndSelect = Mage::getModel('locateandselect/LocateAndSelect');
            $locateAndSelect->updateKialaShippingMethodWithNewKialaPointDetails($shortkpid);
            
            return true;
        } else {
            return false;
        }
    }

}
