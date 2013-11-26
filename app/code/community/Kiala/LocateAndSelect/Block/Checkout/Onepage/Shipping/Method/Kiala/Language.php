<?php
class Kiala_LocateAndSelect_Block_Checkout_Onepage_Shipping_Method_Kiala_Language extends Kiala_LocateAndSelect_Block_Checkout_Onepage_Shipping_Method_Kiala {
    /**
     * Gets the languages dropdown for current checkout session
     * @return html
     */
    public function getLanguageDropdown() {
        $quote = $this->getQuote();
        
        $country = ($quote->getShippingAddress()->getCountry()) ? $quote->getShippingAddress()->getCountry() : Mage::getStoreConfig('general/country/default');
        
        return Mage::helper('locateandselect')->getLanguages($country);
    }
}