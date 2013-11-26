<?php

/**
 * @package 	Kiala_LocateAndSelect
 * @copyright   Copyright (c) 2012 Kiala
 * @author 		Phpro (http://www.phpro.be)
 */
class Kiala_LocateAndSelect_Model_Checkout_ShippingMethod_Observer
{
    protected $_kialaHelper;

    /**
     * @codeCoverageIgnore
     */
    protected function kialaHelper()
    {

        if (is_null($this->_kialaHelper)) {
            $this->_kialaHelper = Mage::helper('locateandselect');
        }
        return $this->_kialaHelper;
    }

    /*
     *  Event triggered when shipping method is saved
     * 
     * @return self
     */

    public function updateKialaAddress($observer)
    {
        if (!$this->kialaHelper()->isActive()) {
            return $this;
        }
        $locateAndSelect = Mage::getModel('locateandselect/LocateAndSelect');
        $locateAndSelect->updateShippingAddressWithKialaPointAddress();
        
        return $this;
    }
    
    public function saveKialaAttributes()
    {
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        $country = $quote->getShippingAddress()->getCountry();
        $lang = $quote->getLanguage();
        if (!isset($lang) || $lang == '' || $lang == null) {
            $quote->setLanguage($this->kialaHelper()->getLangByCountry($country));
        }
        $quote->setDspid($this->kialaHelper()->getDspidForDestination($country))->save();
    }

}
