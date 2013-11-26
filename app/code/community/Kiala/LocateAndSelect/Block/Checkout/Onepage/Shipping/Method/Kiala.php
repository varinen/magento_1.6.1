<?php
class Kiala_LocateAndSelect_Block_Checkout_Onepage_Shipping_Method_Kiala extends Mage_Checkout_Block_Onepage_Shipping_Method_Available {
    protected $_kialaHelper = null;
    private $_locateAndSelect = null;
    private $_kialaPoint = null;
    private $_kialaPointSet = false;
    
    /**
     * Retrieve LocateAndSelect model
     * @return Kiala_LocateAndSelect_Model_LocateAndSelect
     */
    public function getLocateAndSelect()
    {
        if (empty($this->_locateAndSelect)) {
            $this->_locateAndSelect = Mage::getModel("locateandselect/LocateAndSelect");
        }
        return $this->_locateAndSelect;
    }
    
    /**
     * Get the kialapoint 
     * @return Kiala_LocateAndSelect_Model_KialaPoint
     */
    public function getKialaPoint($change = false)
    {
        if (!$this->_kialaPointSet) {
            $kialaPoints = $this->getLocateAndSelect()->getKialaPointsForCurrentCheckoutSession(2, $change);
            $this->_kialaPoint = $kialaPoints;
            $this->_kialaPointSet = true;
        }
        return $this->_kialaPoint;
    }
    
    public function getKialaPointDescription($kialaPoint) {
        $_description = $kialaPoint->getMethodDescription();
        $_previousKialaPoint = $kialaPoint->getPreviousKialaPoint();
        
        if ($_previousKialaPoint) {
            $_description = str_replace("{kialapoint_name}", $_previousKialaPoint->getName(), $_description);
            $_description = str_replace("{reason}", $_previousKialaPoint->getStatus(), $_description);
        }
        
        return $_description;
    }
    
    public function getLocateAndSelectUrl() {
        return $this->getLocateAndSelect()->buildKialaMapRequestUri($this->getQuote()->getShippingAddress());
    }
    
    /**
     * Sets the width for the iframe
     * @return string width
     */
    public function getWidth()
    {
        return Mage::helper('locateandselect')->getMapWidth();
    }

    /**
     * Sets the height for the iframe
     * @return string width
     */
    public function getHeight()
    {
        return Mage::helper('locateandselect')->getMapHeight();
    }
    
    /**
     * Gets the language of the kiala language
     */
    public function getKialaLanguage()
    {
        $store_language = substr(Mage::app()->getLocale()->getLocaleCode(),0,2);
        $quote = $this->getQuote();
        $quote->setLanguage('')->save();
        $quoteLanguage = $quote->getLanguage();
        
        $country = ($quote->getShippingAddress()->getCountry()) ? $quote->getShippingAddress()->getCountry() : Mage::getStoreConfig('general/country/default');
        
        $languageCollection = Mage::getModel('locateandselect/language')->getCollection()->addFieldToFilter('country', $country);
        
        if(count($languageCollection) == 1) {
            //If 1 language available, return and set the language
            $languageItem = $languageCollection->getFirstItem();
            
            $quote->setLanguage($languageItem->getLanguage());
            
            return $languageItem->getLanguage();
        } elseif (count($languageCollection) > 1) {
            //Check if quote language is in collection
            foreach($languageCollection as $languageItem) {
                if ($languageItem->getLanguage() == $store_language) {
                    $quote->setLanguage($store_language);

                    return $store_language;
                }
                if($languageItem->getLanguage() == $quoteLanguage) {
                    $quote->setLanguage($quoteLanguage);
                    
                    return $quoteLanguage;
                }
            }
            
            //If not, return empty and set language empty
            $quote->setLanguage('');
            return '';
        } else {
            //Collection empty, empty the quote language
            $quote->setLanguage('');
            return '';
        }
    }
    
    /**
     * Returns html checked attribute if shipping method must be preselected
     */
    public function getCheckedAttribute($_rate) {
        if ($_rate->getCode() === $this->getAddressShippingMethod() || Mage::helper('locateandselect')->getKialaPreselected()) {
            return ' checked="checked"';
        } else {
            return '';
        }
    }
    
    /**
     * Returns javascript code to show the right popup based on the situation
     * @return string javascript onclick
     */
    public function getShowpopupFunction () {
        $_kialaPoints = $this->getKialaPoint();
        
        if (!isset($_kialaPoints) || empty($_kialaPoints)){
            return 'showKialaWindow(\''.$this->getLocateAndSelectUrl() .'\',\'iframe\', '.($this->getWidth() + 5).','.$this->getHeight().');';
        }else{
            $width = $this->getWidth() + 15;
            $height = 0;
            if (Mage::helper('locateandselect')->showInline()){
                //width + margin of 15px
                //height + top margin of 169px == space required by preselection
                $height = $this->getHeight() + 169;
            }else{
                $height = 181;
            }
            
            //If popup shows more than 1 kialapoint, calculate more height
            if(count($this->getLocateAndSelect()->getKialaPointsForCurrentCheckoutSession(2, true)) > 1) {
                $height += 30;
            }
            
            return 'showKialaWindow(\''.Mage::getUrl('locateandselect/kialapoint/change') .'\',\'ajax\', '.$width.','.$height.');';
        }
    }
}