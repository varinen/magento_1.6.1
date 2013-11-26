<?php

/**
 * @package 	Kiala_LocateAndSelect
 * @copyright   Copyright (c) 2012 Kiala
 * @author 		Phpro (http://www.phpro.be)
 */
class Kiala_LocateAndSelect_Model_LocateAndSelect extends Mage_Core_Model_Abstract
{
    public $_httpClient;
    protected $_kialaHelper;
    protected $_shippingAddress;
    protected $_messages = array(
        "kialapoint_selected_by_user" => "You selected this Kiala Point",
        "kialapoint_previous_order" => "Based on your previous order, we suggest following Kiala point",
        "kialapoint_previous_order_not_available" => "The Kiala point {kialapoint_name} you selected in a previous order is not available. ({reason}) We suggest following Kiala point as alternative"
    );

    public function _construct()
    {
        $this->_httpClient = new Zend_Http_Client(null, $this->getProxyConfig());
    }

    /*
     * gets proxy configuration for Zend_Http_Client
     * @return array
     */

    public function getProxyConfig()
    {
        $proxyHost = $this->kialaHelper()->getProxyHost();
        $proxyPort = $this->kialaHelper()->getProxyPort();
        $proxyLogin = $this->kialaHelper()->getProxyLogin();
        $proxyPassword = $this->kialaHelper()->getProxyPassword();
        // Only use the custom proxy params if they are available
        if (empty($proxyHost)) {
            return array();
        }

        // Set the configuration parameters
        if (empty($proxyLogin)) {
            return array(
                'adapter' => 'Zend_Http_Client_Adapter_Proxy',
                'proxy_host' => $proxyHost,
                'proxy_port' => $proxyPort,
        );
        } else {
            return array(
                'adapter' => 'Zend_Http_Client_Adapter_Proxy',
                'proxy_host' => $proxyHost,
                'proxy_port' => $proxyPort,
                'proxy_login' => $proxyLogin,
                'proxy_password' => $proxyPassword,
            );
        }
    }

    /**
     * @codeCoverageIgnore
     */
    public function kialaHelper($helper = 'data') {
        if (is_null($this->_kialaHelper) || strpos(get_class($this->_kialaHelper), ucfirst($helper)) === false) {
            if (!empty($helper)) {
                $helper = '/' . $helper;
            }
            $this->_kialaHelper = Mage::helper('locateandselect' . $helper);
        }
        return $this->_kialaHelper;
    }

    /**
     * Gets quote from current checkout session
     * @return Mage_Sales_Model_Quote
     */
    protected function getQuote()
    {
        $quote = Mage::getSingleton('checkout/session')->getQuote();
        if (!$quote) {
            return false;
        }
        return $quote;
    }

    /**
     * Gets shippingaddress of current checkout session
     * @return Mage_Sales_Model_Quote_Address
     */
    protected function getShippingAddressForCurrentCheckoutSession()
    {
        if (is_null($this->_shippingAddress)) {
            $quote = $this->getQuote();
            if (!$quote)
                return false;

            $shippingAddress = $quote->getShippingAddress();
            if (!$shippingAddress->getId())
                return false;
            $this->_shippingAddress = $shippingAddress;
        }
        return $this->_shippingAddress;
    }

    /**
     * gets customer id from quote
     */
    protected function getCustomerId()
    {
        $quote = $this->getQuote();
        if (!$quote || $quote->getCustomerId() == "")
            return null;
        return $quote->getCustomerId();
    }

    public function getKialaPointFromPreviousOrder()
    {
        if (!Mage::helper('customer')->isLoggedIn())
            return null;

        $quote = $this->getQuote();
        if (!$quote)
            return null;

        $customerId = $quote->getCustomerId();
        if (is_null($customerId))
            return null;

        $country = $quote->getShippingAddress()->getCountry();
        $order = Mage::getModel("locateandselect/orders")->getPreviousKialaOrder($customerId, $country);
        if (is_null($order))
            return null;

        // shipping address based suggestion
        $post = Mage::app()->getRequest()->getPost();
        $orderAddressId = $order->getShippingAddress()->getCustomerAddressId();
        $quoteAddressId = $quote->getShippingAddress()->getCustomerAddressId();
        if (isset($post['billing_address_id'])) {
            $destinationAddressId = $post['billing_address_id'];
        } else if (isset($post['shipping_address_id'])) {
            $destinationAddressId = $post['shipping_address_id'];
        } else if (isset($quoteAddressId)) {
            Mage::log('quote', Zend_Log::DEBUG, 'kiala.log');
            $destinationAddressId = $quoteAddressId;
        } else {
            $destinationAddressId = null;
        }
        
        if($quoteAddressId == null){
            $destinationAddressId = $orderAddressId;
        }
        
        $shortkpid = $order->getShippingAddress()->getKpId();
        
        //$shortkpid = $this->kialaHelper()->getShortIdFromShippingMethodName($order->getShippingMethod());
        Mage::Log("shortkpid of previous Kiala order:" . $shortkpid, Zend_Log::INFO, 'kiala.log');
        $kialaPoints = $this->getKialaPointsForShortId($shortkpid, $order->getShippingAddress());
        if (count($kialaPoints) < 1) {
            Mage::Log("KialaPoint [' . $shortkpid . '] no longer exists", Zend_Log::INFO, 'kiala.log');
            return null;
        }
 
        if ($kialaPoints[0]->isAvailable() && $orderAddressId == $destinationAddressId) {
            Mage::Log("Kialapoint from previous order selected", Zend_Log::INFO, 'kiala.log');
            $kialaPoints[0]->setMethodDescription($this->_messages["kialapoint_previous_order"]);
            return $kialaPoints;
        } else if ($kialaPoints[0]->isAvailable()) {
            Mage::log('Customer is using other shipping address. Looking up new (and propably closer) Kiala Point.', Zend_Log::INFO, 'kiala.log');
            return null;
        } else {
            Mage::Log("Kialapoint from previous order not available", Zend_Log::INFO, 'kiala.log');
//            $previousKialaPoint = $kialaPoints[0];
//            $kialaPoints = $this->getKialaPointsForAddress($order->getShippingAddress(), 1);
//            $kialaPoints[0]->setPreviousKialaPoint($previousKialaPoint);
//            $kialaPoints[0]->setMethodDescription($this->_messages["kialapoint_previous_order_not_available"]);
//            return $kialaPoints;
        }

        return null;
    }

    /**
     * returns the Kiapoint nearest to the location given in the checkout session
     * @return array KialaPoints
     */
    public function getKialaPointsForCurrentCheckoutSession($numberOfResults = 2, $change = false)
    {

        if (!$this->kialaHelper()->isActive()) {
            return null;
        }

        $shortkpid = null;
        $kialaPoints = array();

        $shippingAddress = $this->getShippingAddressForCurrentCheckoutSession();
        if (!$shippingAddress) {
            Mage::Log("No shipping address found. Unable to check for Kiala point.", Zend_Log::INFO, 'kiala.log');
            return $kialaPoints;
        }

        if (!$change) { // if the user wants to change, we dont need to get the currently selected kp
            $shortkpid = $this->kialaHelper()->getKialaPointId();
            if ($shortkpid != "" && $shortkpid != 'kiala') {
                Mage::Log("Using Kialapoint [" . $shortkpid . "] selected by customer", Zend_Log::INFO, 'kiala.log');
                $result = $this->getKialaPointsForShortId($shortkpid, $shippingAddress);
                if (count($result) > 0) {
                    $kialaPoints[] = $result[0];
                    $kialaPoints[0]->setMethodDescription($this->_messages["kialapoint_selected_by_user"]);
                    return $kialaPoints; // user has selected a kialapoint. remove this statement to still add extra choices.
                } else {
                    Mage::Log("Failed to load data of currently selected Kialapoint with shortkpid [" . $shortkpid . "]", Zend_Log::INFO, 'kiala.log');
                    $kialaPoints = null;
                }
            }
        }

        $previousOrderKP = $this->getKialaPointFromPreviousOrder();
        if ($previousOrderKP != null) {
            if (empty($kialaPoints)) {
                $kialaPoints[] = $previousOrderKP[0];
            } else if ($previousOrderKP[0]->getShortId() != $kialaPoints[0]->getShortId()) {
                $kialaPoints[] = $previousOrderKP[0];
            }
        }
        
        if (count($kialaPoints) < $numberOfResults) {
            Mage::Log("Getting nearest available point.", Zend_Log::INFO, 'kiala.log');
            $result = $this->getKialaPointsForAddress($shippingAddress, 1);//$numberOfResults);
            if ($result) {
                foreach ($result as $kp) {
                    if (count($kialaPoints) == 0) {
                        $kialaPoints = $result;
                    } else if (count($kialaPoints) < $numberOfResults) {
                        foreach($kialaPoints as $kialaPoint) {
                            if ($kialaPoint->getShortId() != $kp->getShortId()) {
                                $kialaPoints[] = $kp;
                            }
                        }
                    }
                }
            } else {
                Mage::Log("No Kiala points found.", Zend_Log::INFO, 'kiala.log');
            }
        }
        
        return $kialaPoints;
    }

    /**
     * Returns specified number of kialapoints based on given address
     */
    protected function getKialaPointsForAddress($shippingAddress, $numberOfResults)
    {
        $uri = $this->buildNearestKialaPointRequestUri($shippingAddress, $numberOfResults, "ACTIVE_ONLY");
        return $this->getKialaPoints($uri);
    }

    /**
     * Returns Kialapoint linked to the given shortId
     * return array KialaPoint
     */
    protected function getKialaPointsForShortId($shortkpid, $shippingAddress = null)
    {

        if ($shortkpid == null) {
            Mage::Log("No Shortkpid given: [" . $shortkpid . "]", Zend_Log::INFO, 'kiala.log');
            return array();
        }

        if ($shippingAddress == null) {
            $shippingAddress = $this->getShippingAddressForCurrentCheckoutSession();
        }
        $uri = $this->buildKialaPointShortIdRequestUri($shortkpid, $shippingAddress);
        return $this->getKialaPoints($uri);
    }

    /**
     * Retrieve e the nearest Kiala points for this address
     * @return array KialaPoints
     */
    public function getKialaPoints($uri)
    {
        if (stristr($uri, 'dspid=&') !== false) {
            Mage::Log("No DPSID in url: [" . $uri . "]", Zend_Log::INFO, 'kiala.log');
            return null;
        }

        try
        {
            Mage::Log("Request Kiala Point details on: [" . $uri . "]", Zend_Log::INFO, 'kiala.log');
            $this->_httpClient->setUri($uri);
            $response = $this->_httpClient->request("GET");
            if ($response->isError()) {
                Mage::Log("Kiala: Status: " . $response->getStatus() . " - Message:" . $response->getMessage() . "", Zend_Log::ERR, 'kiala.log');
                return array();
            }
            return $this->transformXmlToKialaPoints($response->getBody());
        } catch (Exception $e)
        {
            Mage::Log("Kiala: Could not reach service at [" . $uri . "] with message: " . $e->getMessage(), Zend_Log::ERR, 'kiala.log');
            return null;
        }
    }

    /**
     * Assembles url for request to get the Kialapoint located nearest to given shipping address
     * @return string
     */
    public function buildNearestKialaPointRequestUri($shippingAddress, $numberOfResults = 1, $sortMethod = "ALL")
    {
        $frontendUrl = $this->kialaHelper('url')->getFrontendUrl($this->buildNearestKialaPointqueryString($shippingAddress, $numberOfResults, $sortMethod));
        $explode = explode('?', $frontendUrl);
        $requestUri = $explode[0] . '/kplist?' . $explode[1];
        
        return $requestUri;
        //return $this->kialaHelper('url')->getFrontendUrl() . '/kplist' . $this->buildNearestKialaPointqueryString($shippingAddress, $numberOfResults, $sortMethod);
    }

    /**
     * Assembles url for request to get the Kialapoint by its shortId
     * @return string
     */
    public function buildKialaPointShortIdRequestUri($shortkpid, $shippingAddress)
    {
        $frontendUrl = $this->kialaHelper('url')->getFrontendUrl($this->buildKialaPointShortIdqueryString($shortkpid, $shippingAddress));
        $explode = explode('?', $frontendUrl);
        $requestUri = $explode[0] . '/kplist?' . $explode[1];
        return $requestUri;
        //return $this->kialaHelper('url')->getFrontendUrl() . '/kplist' . $this->buildKialaPointShortIdqueryString($shortkpid, $shippingAddress);
    }

    /**
     * Assembles url to show the map with Kialapoint in your neighbourhood
     * @return string
     */
    public function buildKialaMapRequestUri($shippingAddress)
    {
        $frontendUrl = $this->kialaHelper('url')->getFrontendUrl($this->buildKialaMapRequestQueryString($shippingAddress));
        $explode = explode('?', $frontendUrl);
        $requestUri = $explode[0] . '/search?' . $explode[1];
        return $requestUri;        
    }

    /**
     * Assembles query string for request to get the Kialapoint located nearest to given shipping address
     * @return string
     */
    public function buildNearestKialaPointqueryString($shippingAddress, $numberOfResults = 1, $sortMethod = "ALL")
    {
        $parameters = $this->buildCommonQueryStringParameters($shippingAddress);
        $parameters['sort-method'] = urlencode($sortMethod);
        $parameters['max-result'] = urlencode($numberOfResults);
        return $parameters;
        
        /*return  $this->buildCommonQueryStringParameters($shippingAddress) .
                '&sort-method=' . urlencode($sortMethod) .
                '&max-result=' . urlencode($numberOfResults);*/
    }

    /**
     * Assembles query string for Kiala Locate and select map
     * @return string
     */
    public function buildKialaMapRequestQueryString($shippingAddress)
    {
        $bckurl = Mage::getUrl('locateandselect/map/selectonmap', array("_secure" => true));
        
        $parameters = $this->buildCommonQueryStringParameters($shippingAddress);
        $parameters['bckUrl'] = urlencode($bckurl . '?');
        $parameters['target'] = urlencode("_self");
        $parameters['map-controls'] = urlencode("off");
        $parameters['thumbnails'] = urlencode("off");
        
        return $parameters;
    }

    /**
     * Processes selection of new Kiala point
     *  returns boolean
     */
    public function updateKialaShippingMethodWithNewKialaPointDetails($shortkpid)
    {

        Mage::Log("updating shipping method to include selected KialaPoint", Zend_Log::INFO, 'kiala.log');

        $shippingAddress = $this->getShippingAddressForCurrentCheckoutSession();
        if (!$shippingAddress) {
            Mage::Log("No shipping method available. Cancelling update of shipping method", Zend_Log::INFO, 'kiala.log');
            return false;
        }
        $kialaPoints = $this->getKialaPointsForShortId($shortkpid, $shippingAddress);
        if (!is_array($kialaPoints) || count($kialaPoints) < 1)
            return false;
        $kialaPoint = $kialaPoints[0];

        $collection = $shippingAddress->getShippingRatesCollection();
        foreach ($collection as $key => $rate)
        {
            if ($rate->getCarrier() == "kiala") {
                Mage::Log('Overriding shipping method with id [' . $shippingAddress->getId() . '] : From [' .
                    $shippingAddress->getShippingMethod() . '] to [' . $rate->getCode() . ']', Zend_Log::INFO,
                    'kiala.log');
                $shippingAddress->setShippingMethod($rate->getCode());
                $shippingAddress->setShippingDescription($rate->getMethodTitle());

                $rate->setMethodDescription($this->_messages["kialapoint_selected_by_user"]);
                $rate->save();
            }
        }

        Mage::Log("Updating ShippingMethod to: " . $kialaPoint->getCode(), Zend_Log::INFO, 'kiala.log');
        $shippingAddress->setKpId($kialaPoint->getShortId());
        $shippingAddress->save();
        
        $this->updateBillingAddressWithKialaPoint($kialaPoint->getShortId());
        $this->updateShippingAddressWithKialaPointAddress();
        
        $shippingMethodObserver = new Kiala_LocateAndSelect_Model_Checkout_ShippingMethod_Observer();
        $shippingMethodObserver->saveKialaAttributes();
        
        return true;
    }
    
    public function updateBillingAddressWithKialaPoint($kpId) {
        Mage::getSingleton('checkout/session')->getQuote()->getBillingAddress()->setKpId($kpId)->save();
    }

    /**
     * function to update the shipping address with the selected kialapoint's address
     * @return boolean
     */
    public function updateShippingAddressWithKialaPointAddress()
    {

        if (!$this->kialaHelper()->isActive())
            return;

        $shippingAddress = $this->getShippingAddressForCurrentCheckoutSession();
        if (!$shippingAddress)
            return;
        if (!$this->kialaHelper()->isKialaShippingMethod($shippingAddress->getShippingMethod()))
            return;

        // Save the current address for export later
        $customerAddress = Mage::getModel('locateandselect/customeraddress');
        $customerAddress->setQuote($this->getQuote()->getId())
                ->setAddress(serialize($shippingAddress->debug()))
                ->save();
        if ($shippingAddress->getSaveInAddressBook()) {
            $this->createShippingAddressForCustomer($shippingAddress);
        }

        $kialaPoints = $this->getKialaPointsForCurrentCheckoutSession();

        if (!is_array($kialaPoints) || count($kialaPoints) < 1)
            return;
        $kialaPoint = $kialaPoints[0];

        $shippingAddress->setCompany($kialaPoint->getName());
        $shippingAddress->setStreet($kialaPoint->getStreet());
        $shippingAddress->setPostcode($kialaPoint->getPostcode());
        $shippingAddress->setCity($kialaPoint->getCity());
        $shippingAddress->setSameAsBilling(true);
        $shippingAddress->setKpId($kialaPoint->getShortId());
        return $shippingAddress->save();
    }

    /**
     * returns part of querystring common to all rest requests to kiala
     * @return string
     */
    private function buildCommonQueryStringParameters($shippingAddress)
    {
        // Address related parameters
        $street = implode(" ", $shippingAddress->getStreet());
        $postcode = $shippingAddress->getPostcode();
        $city = $shippingAddress->getCity();
        $country = $shippingAddress->getCountry();
        // Helper related parameters
        $dspid = $this->kialaHelper()->getDspidForDestination($country);
        $language = $this->kialaHelper()->getStoreLang();
        $preparationDelay = $this->kialaHelper()->getPreparationDelay();
        
        /*return
                '?dspid=' . urlencode($dspid) .
                '&country=' . urlencode($country) .
                '&language=' . urlencode($language) .
                '&preparationdelay=' . urlencode($preparationDelay) .
                '&street=' . urlencode($street) .
                '&zip=' . urlencode($postcode) .
                '&city=' . urlencode($city);*/
        
        return array(
            'dspid' => urlencode($dspid),
            'country' => urlencode($country),
            'language' => urlencode($language),
            'preparationdelay' => urlencode($preparationDelay),
            'street' => urlencode($street),
            'zip' => urlencode($postcode),
            'city' => urlencode($city),
        );
    }

    /**
     * Assembles query string for request to get the Kialapoint by its shortId
     * 
     * return string 
     */
    public function buildKialaPointShortIdqueryString($shortkpid, $shippingAddress)
    {
        // Helper related parameters
        $country = $shippingAddress->getCountry();
        $dspid = $this->kialaHelper()->getDspidForDestination($country);
        
        return array(
            'dspid' => urlencode($dspid),
            'country' => urlencode($country),
            'shortID' => urlencode($shortkpid),
        );
        
        /*return  '?dspid=' . urlencode($dspid) .
                '&country=' . urlencode($country) .
                '&shortID=' . urlencode($shortkpid);*/
    }

    /**
     * Parses returned xml and transforms returned kialpoint as objects
     * @return array Kiala_LocateAndSelect_Model_KialaPoint
     */
    public function transformXmlToKialaPoints($xmlString)
    {
        $xmlObject = $this->parseXml($xmlString);
        $kialaPoints = array();
        foreach ($xmlObject->kp as $kialapoint)
        {
            $kialaPoints[] = new Kiala_LocateAndSelect_Model_KialaPoint($kialapoint);
        }
        Mage::log('Received ' . count($kialaPoints) . ' Kiala points.', Zend_Log::INFO, 'kiala.log');
        return $kialaPoints;
    }

    /**
     * This methods tries to create a simplexml object from the given xmlstring
     * @param stringxml $xml
     * @throws Exception
     */
    private function parseXml($xmlString)
    {
        libxml_use_internal_errors(true);
        $xmlObject = simplexml_load_string($xmlString);
        if (!$xmlObject) {
            Mage::Log('Unable to parse xml. \n' . $xmlString, Zend_Log::ERR, 'kiala.log');
            foreach (libxml_get_errors() as $error)
            {
                Mage::Log($error->message, Zend_Log::ERR, 'kiala.log');
            }
            throw new Exception("Unable to parse xml", 10);
        }
        return $xmlObject;
    }

    private function createShippingAddressForCustomer($shippingAddress)
    {
        $checkoutMethod = Mage::getSingleton('checkout/cart')->getQuote()->getCheckoutMethod();
        if ($checkoutMethod == 'login_in') {
            $customer = $this->getQuote()->getCustomer();
            $address = Mage::getModel("customer/address");
            $address->setCustomerId($customer->getId());
            $address->firstname = $customer->firstname;
            $address->lastname = $customer->lastname;
            $address->country_id = $shippingAddress->getCountryId(); //Country code here
            $address->postcode = $shippingAddress->getPostcode();
            $address->city = $shippingAddress->getCity();
            $address->telephone = $shippingAddress->getTelephone();
            $address->fax = $shippingAddress->getFax();
            $address->company = $shippingAddress->getCompany();
            $address->street = $shippingAddress->getStreet();

            $customer->addAddress($address);
            $customer->save();
        }
    }

}
