<?php

class Kiala_LocateAndSelect_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * returns true if Kiala is enabled
     * @return boolean active
     */
    public function isActive() {
        return Mage::getStoreConfig('carriers/kiala/active') == 1 && $this->getSenderCountry() != '';
    }
    
    /**
     * Returns true if the onestepcheckout is enabled
     * @return boolean
     */
    public function isOneStepCheckout() {
        return Mage::getStoreConfig('onestepcheckout/general/rewrite_checkout_links');
    }

    /**
     * returns true if desktop_app_user has value 2: UPS Worldship CSV Export
     * @return boolean
     */
    public function isUpsWorldshipUser() {
        return Mage::getStoreConfig('carriers/kiala/desktop_app_user') == 2;
    }

    /**
     * returns true if desktop_app_user has value 1: Pack&Ship - Type = Pack&Ship Desktop
     * @return boolean desktop_app_user
     */
    public function isDesktopAppUser() {
        return Mage::getStoreConfig('carriers/kiala/desktop_app_user') == 1;
    }

    /**
     * returns true if 'Table Rates' is selected
     * @return boolean use_tablerates
     */
    public function useTablerates() {
        return Mage::getStoreConfig('carriers/kiala/use_tablerates') == 1;
    }

    /**
     * returns true if 'Advanced Table Rates' is selected
     * @return bool
     */
    public function useOwebiaRates() {
        return (Mage::helper('core')->isModuleEnabled('Owebia_Shipping2') &&
            Mage::helper('core')->isModuleEnabled('Kiala_OwebiaShipping2') &&
            Mage::getStoreConfig('carriers/kiala/use_tablerates') == 2);
    }

    /**
     * returns true if use_tablerates is enabled
     * @return boolean use_tablerates
     */
    public function getFlatFeeShippingCost() {
        return Mage::getStoreConfig('carriers/kiala/flatfee_shippingcost');
    }

    /**
     * returns kiala_sender_id
     * @return string kiala_sender_id
     */
    public function getKialaSenderId() {
        return Mage::getStoreConfig('carriers/kiala/kiala_sender_id');
    }

    /**
     * returns dspid
     * @return string dspid
     */
    public function getDspidForDestination($destinationCountry) {
        $dspid = Mage::getModel("locateandselect/dspid");
        return $dspid->getDSPIDForDestination($this->getSenderCountry(), $destinationCountry);
    }

    /**
     * returns kiala_password
     * @return string kiala_password
     */
    public function getPassword() {
        return trim(Mage::getStoreConfig('carriers/kiala/kiala_password'));
    }

    /**
     * returns sender_country
     * @return string sender_country
     */
    public function getSenderCountry() {
        return trim(Mage::getStoreConfig('carriers/kiala/sender_country'));
    }

    /**
     * returns show_inline 
     * @return boolean show_inline
     */
    public function showInline() {
        return Mage::getStoreConfig('carriers/kiala/show_inline');
    }

    /**
     * returns preparation_delay 
     * @return int preparation_delay
     */
    public function getPreparationDelay() {
        return trim(Mage::getStoreConfig('carriers/kiala/preparation_delay'));
    }

    /**
     * returns proxy_host 
     * @return string proxy_host
     */
    public function getProxyHost() {
        return trim(Mage::getStoreConfig('carriers/kiala/proxy_host'));
    }

    /**
     * returns proxy_port 
     * @return string proxy_port
     */
    public function getProxyPort() {
        return trim(Mage::getStoreConfig('carriers/kiala/proxy_port'));
    }
    
    /**
     * returns proxy_port 
     * @return string proxy_port
     */
    public function getProxyLogin() {
        return trim(Mage::getStoreConfig('carriers/kiala/proxy_login'));
    }
    
    /**
     * returns proxy_port 
     * @return string proxy_port
     */
    public function getProxyPassword() {
        return trim(Mage::getStoreConfig('carriers/kiala/proxy_password'));
    }

    /**
     * returns store_language 
     * @return string store_language
     */
    public function getStoreLang() {
        $langCode = explode('_', Mage::app()->getLocale()->getLocaleCode());
        return strtolower($langCode[0]);
    }

    /**
     * returns map width 
     * @return string map_width
     */
    public function getMapWidth() {
        $width = trim(Mage::getStoreConfig('carriers/kiala/map_width'));
        return ($width == "" || $width < 0 ? "640" : $width);
    }

    /**
     * returns map height 
     * @return string map_height
     */
    public function getMapHeight() {
        $height = trim(Mage::getStoreConfig('carriers/kiala/map_height'));
        return ($height == "" || $height < 0 ? "640" : $height);
    }

    /**
     * checks if given shippingmethod string contains Kiala 
     * @return boolean
     */
    public function isKialaShippingMethod($shipping_method_string) {
        if ($shipping_method_string == "")
            return false;
        return (strpos($shipping_method_string, "kiala") !== false);
    }

    /**
     * function to get shortID of a given methos
     * @return string 
     */
    public function getShortIdFromShippingMethodName($shipping_method_string) {
        if (!$this->isKialaShippingMethod($shipping_method_string))
            return null;
        $kpId = substr($shipping_method_string, strrpos($shipping_method_string, "_") + 1);
        
        if (is_numeric($kpId)) {
            return $kpId;
        } else {
            return '';
        }
    }

    /**
     * returns path to image of the kiala logo
     * @return string path
     */
    public function getLogoPath() {
        return 'images/kiala/locateandselect/kiala-logo.jpg';
    }

    /**
     * Returns the first language in the list for a given country, false if no valid languages
     * @param string $country
     * @return mixed $language 
     */
    public function getLangByCountry($country) {
        $collection = Mage::getModel('locateandselect/language')
                ->getCollection()
                ->addFieldToFilter('country', $country);

        foreach ($collection as $language) {
            if ($language['country'] == $country) {
                $languages[] = $language['language'];
            }
        }
        if (in_array($this->getStoreLang(), $languages)) {
            return $this->getStoreLang();
        } else if (!empty($languages)) {
            return $languages[0];
        }
        return false;
    }

    /**
     * Returns a dropdown with languages for a given country
     * @param string $country
     * @return string $select
     */
    public function getLanguages($country) {
        $collection = Mage::getModel('locateandselect/language')
                ->getCollection();
        $collection->addFieldToFilter('country', $country);


        $select = "<select id='kiala-language'><option value=''>-- select --</option>";
        foreach ($collection as $language) {
            $select .= "<option value='" . $language['language'] . "'>" . $language['description'] . '</option>';
        }
        $select .= '</select>';

        return $select;
    }

    /**
     * Returns the language of the notification if it has been chosen, false otherwise
     * @param Mage_Sales_Model_Order $order
     * @return mixed $language
     */
    public function getCustomerNotificationLanguage($order = '') {
        if (!empty($order)) {
            $lang = $order->getLanguage();
            if (!empty($lang)) {
                return $lang;
            }
        }
        $country = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getCountry();
        $storeLocale = explode('_', Mage::app()->getLocale()->getLocaleCode());
        $storeCountry = $storeLocale[1];

        $country = (empty($country)) ? $storeCountry : $country;
        return $this->getLangByCountry($country);
    }

    /**
     * Returns if Kiala shipping method should be preselected
     * @return type 
     */
    public function getKialaPreselected() {
        return Mage::getStoreConfig('carriers/kiala/preselected');
    }

    /**
     * Adds 'Shipping By Kiala' in red to the shipping addres if it was shipped by Kiala
     * @param string $shipping
     * @param string $shippingMethod
     * @return string $shipping
     */
    public function formatShipping($shipping, $shippingMethod, $order = '', $email = false) {
        if ($this->isKialaShippingMethod($shippingMethod)) {
            $shippedByKiala = "\n<span style=\"color:#D0003A;\"><strong>" . $this->__('Shipping by Kiala to:') . '</strong></span><br />';
            $split = strpos($shipping, '<br/>') + strlen('<br/>');
        
            $name = substr($shipping, 0, $split);
            $address = substr($shipping, $split);
        
            $kialaSplit = strpos($address, '<br />');
            $shortId = $this->getShortIdFromShippingMethodName($shippingMethod);
            if ($order == '') {
                $order = Mage::getSingleton('checkout/session')->getQuote();
            } 

            if ($email) {
                $kialaPoint = '<a class="kp_name" href="' . Mage::helper('locateandselect/url')->getKPDetailsUrl($order->getShippingAddress()->getKpId(), $order) . '">' . substr($address, 0, $kialaSplit) . '</a>' . substr($address, $kialaSplit);
            } else {
                $kialaPoint = '<a href="javascript:void(0);" class="kp_name" onclick="showKialaWindow(\'' . Mage::helper('locateandselect/url')->getKPDetailsUrl($order->getShippingAddress()->getKpId(), $order) . '\',\'iframe\', ' . $this->getMapWidth() . ', ' . $this->getMapHeight() .')">' . substr($address, 0, $kialaSplit) . '</a>' . substr($address, $kialaSplit);
            }
            $shippingKiala = $name . $shippedByKiala . $kialaPoint;
        
        
            return $shippingKiala;
        }
        return $shipping;
    }

    /**
     * Checks if the user already has selected a Kialapoint during checkout
     * @return boolean 
     */
    public function kialaPointChosen() {
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        if ($quote) {
            $shippingAddress = $quote->getShippingAddress();
            if ($shippingAddress) {
                if ($this->isKialaShippingMethod($shippingAddress->getShippingMethod())) {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * Returns kialapoint id for current checkout session
     * @return string kialapointid
     */
    public function getKialaPointId() {
        $quote = Mage::getSingleton('checkout/session')->getQuote();
//        return Mage::log(print_r($quote->getShippingAddress()->getData(),true));
        if($quote->getShippingAddress()->getKpId()) {
            return $quote->getShippingAddress()->getKpId();
        } else {
            $shippingmethod = $quote->getShippingAddress()->getShippingMethod();
            
            if($this->isKialaShippingMethod($shippingmethod)) {
                return $this->getShortIdFromShippingMethodName($shippingmethod);
            } else {
                return '';
            }
        }
        
    }
    
    /**
     * Returns which edition of Magento is being used. Professional and Enterprise are viewed as identical.
     * @return string 
     */
    public function getMagentoEdition() {
        if (method_exists('Mage', 'getEdition')) {
            return Mage::getEdition();
        }
        
        if (file_exists('LICENSE_EE.txt') || file_exists('LICENSE_PE.txt')) {
            return 'Enterprise';
        }
        return 'Community';
    }

    /**
     * Get the current version of the Kiala Module
     * @return string
     */
    public function getVersion() {
        return Mage::getConfig()->getModuleConfig("Kiala_LocateAndSelect")->version;
    }
}
