<?php

class Kiala_LocateAndSelect_Helper_Url extends Mage_Core_Helper_Abstract
{
    /**
     * returns frontend_url
     * @return string frontend_url
     */
    public function getFrontendUrl($parameters = array()) {
        $baseUrl = trim(Mage::getStoreConfig('carriers/kiala/frontend_url'));
        $parameters = $this->formatParameters($parameters, 'frontend_url');
        return $baseUrl . $parameters;
    }

    /**
     * returns api_uri
     * @return string api_uri
     */
    public function getApiUri($parameters = array()) {
        $baseUrl = trim(Mage::getStoreConfig('carriers/kiala/api_uri'));
        if (empty($parameters)) {
            $parameters = array(
                'wsdl' => '',
            );
        }
        $parameters = $this->formatParameters($parameters, 'api_uri');
        return $baseUrl . $parameters;
    }

    /**
     * returns trackandtrace_uri
     * @return string trackandtrace_uri
     */
    public function getTrackAndTraceUri(array $parameters) {
        $baseUrl = trim(Mage::getStoreConfig('carriers/kiala/trackandtrace_uri'));
        $parameters = $this->formatParameters($parameters, 'trackandtrace_uri');
        return $baseUrl . $parameters;
    }

    /**
     * returns kp_details_url
     * @return string kp_details_url
     */
    public function getKPDetailsUrl($kp, $order) {
        $baseUrl = trim(Mage::getStoreConfig('carriers/kiala/kp_details_url'));
        $lang = Mage::helper('locateandselect')->getCustomerNotificationLanguage($order);
        
        $shortId = (is_object($kp)) ? $kp->getShortId() : $kp;
        
        if ($lang == false) {
            $lang = '';
            $country = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getCountry();
        } else {
            $country = $order->getShippingAddress()->getCountry();
        }

        $parameters = array(
            'countryid' => $country,
            'language' => $lang,
            'shortID' => $shortId,
            'map' => 'on',
            'align' => 'left',
        );
        $parameters = $this->formatParameters($parameters, 'kp_details_url');
        return $baseUrl . $parameters;
    }

    /**
     * formats the parameters of a url
     * @param array $parameters
     * @param string $path
     * @return type 
     */
    private function formatParameters($parameters, $path) {
        if (is_array($parameters)) {
            $parameterOverrides = unserialize(Mage::getStoreConfig("carriers/kiala/{$path}_parameters"));
            if (is_array($parameterOverrides)) {
                foreach ($parameterOverrides as $override) {
                    $parameters[$override['param']] = $override['val']; // existing values will be overridden, new ones wil be added.
                }
            }

            $queryString = '?';
            foreach ($parameters as $key => $value) {
                if (!empty($value)) {
                    $queryString .= $key . '=' . $value . '&';
                } else {
                    $queryString .= $key . '&';
                }
            }
            $queryString = substr($queryString, 0, -1); // strip last '&'

            return $queryString;
        }
        return $parameters;
    }

}