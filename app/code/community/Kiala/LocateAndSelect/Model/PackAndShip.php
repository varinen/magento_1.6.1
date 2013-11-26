<?php

class Kiala_LocateAndSelect_Model_PackAndShip extends Mage_Core_Model_Abstract
{
    /**
     * Kiala Helper
     * @var Kiala_LocateAndSelect_Helper_Data  
     */
    protected $_kialaHelper;
    
    /**
     * @codeCoverageIgnore
     */
    public function kialaHelper($helper = '') {
        if (is_null($this->_kialaHelper) || $helper == '' || strpos(get_class($this->_kialaHelper),  ucfirst($helper)) === false) {
            if (!empty($helper)) {
                $helper = '/' . $helper;
            }
            $this->_kialaHelper = Mage::helper('locateandselect' . $helper);
        }
        return $this->_kialaHelper;
    }

    /**
     * Handles the call to the Kiala PackAndShip webservice
     * @param integer $shipmentId
     * @return string Trackingcode
     */
    public function getTrackingCodeFromKiala($shipmentId) {
        $params = $this->getWSParameters($shipmentId);
        $wsUri = $this->kialaHelper('url')->getApiUri();
        $proxyConfig = array(
            'proxy_host' => $this->kialaHelper()->getProxyHost(),
            'proxy_port' => $this->kialaHelper()->getProxyPort()
                );
        
        $proxy_login = $this->kialaHelper()->getProxyLogin();
        $proxy_password = $this->kialaHelper()->getProxyPassword();
        
        if (!empty($proxy_login) && !empty($proxy_password)) {
            $proxyConfig['proxy_login'] = $proxy_login;
            $proxyConfig['proxy_password'] = $proxy_password;
        }

        try {
            if ($proxyConfig['proxy_host'] == '' || $proxyConfig['proxy_port'] == '') {
                if (file_get_contents($wsUri)) { // prevent fatal error if unreachable webservice
                    Mage::log('Calling PackAndShip webservice for shipment ' . $shipmentId, Zend_Log::INFO, 'kiala.log');
                    $client = new SoapClient($wsUri);
                }
            } else {
                $options = array('http' => array('proxy' => 'tcp://' . $proxyConfig['proxy_host'] . ':' . $proxyConfig['proxy_port'], 'request_fulluri' => true));
                $streamContext = stream_context_create($options);
                if (file_get_contents($wsUri, NULL, $streamContext)) { // prevent fatal error if unreachable webservice
                    Mage::log('Calling PackAndShip webservice for shipment ' . $shipmentId . ' with proxy: ' . $proxyConfig['proxy_host'] . ':' . $proxyConfig['proxy_port'], Zend_Log::INFO, 'kiala.log');
                    $client = new SoapClient($wsUri, $proxyConfig);
                }
            }

            $trackingNumber = $client->createOrder($params);
            Mage::log('Received tracking code ' . $trackingNumber->trackingNumber, Zend_Log::INFO, 'kiala.log');
        } catch (Exception $e) {
            if ($e->detail->orderFault) {
                Mage::log($e->detail->orderFault->faultCode . ' : ' . $e->detail->orderFault->message, Zend_Log::ERR, 'kiala.log');
            } else {
                Mage::log($e->getMessage(), Zend_Log::ERR, 'kiala.log');
                Mage::log('Tracking code not created. Probably already requested...', Zend_Log::ERR, 'kiala.log');
            }
            $trackingNumber = new stdClass();
            $trackingNumber->trackingNumber = false;
        }

        return $trackingNumber->trackingNumber;
    }

    /**
     * Builds the array with options for the Kiala PackAndShip webservice call
     * @param integer $shipmentId
     * @return array Options
     */
    public function getWSParameters($shipmentId) {
        Mage::log('Building options array for webservice call', Zend_Log::INFO, 'kiala.log');

        $storeId = Mage::getModel('sales/order_shipment')->loadByIncrementId($shipmentId)->getOrder()->getStoreId();
        $kialaSenderId = trim(Mage::getStoreConfig('carriers/kiala/kiala_sender_id', $storeId));
        $kialaPassword = trim(Mage::getStoreConfig('carriers/kiala/kiala_password', $storeId));
        $fromCountry = trim(Mage::getStoreConfig('carriers/kiala/sender_country', $storeId));

        $orderDetails = $this->getOrderDetailsForTrackingCode($shipmentId);

        $hash = hash('sha512', $shipmentId . $kialaSenderId . $kialaPassword);

        $params = array(
            'reference' => $shipmentId,
            'identification' => array('sender' => $kialaSenderId, 'hash' => $hash, 'originator' => 'mag_1.3.0'),
            'delivery' => array(
                'from' => array('country' => $fromCountry,
                    'node' => ''),
                'to' => array('country' => $orderDetails['toCountry'],
                    'node' => $orderDetails['kialaPointId']),
            ),
            'parcel' => array('description' => '',
                'weight' => $orderDetails['productWeight'],
                'orderNumber' => $shipmentId,
                'orderDate' => $orderDetails['orderDate']
            ),
            'receiver' => array('firstName' => $orderDetails['customerFirstName'],
                'surname' => $orderDetails['customerLastName'],
                'address' => array(
                    'line1' => $orderDetails['street'],
                    'line2' => '',
                    'postalCode' => $orderDetails['postcode'],
                    'city' => $orderDetails['city'],
                    'country' => $orderDetails['toCountry']),
                'email' => $orderDetails['email'],
                'language' => 'nl'
                ));

        return $params;
    }

    /**
     * Retrieves data from the order and shipment for the options array.
     * @param integer $shipmentId
     * @return array Orderdata
     */
    public function getOrderDetailsForTrackingCode($shipmentId) {
        Mage::log('Retrieving data from order and shipment for options array', Zend_Log::INFO, 'kiala.log');

        $orderDetails = array();
        $shipmentSuffix = explode('_', $shipmentId);
        $order = Mage::getModel('sales/order_shipment')->loadByIncrementId($shipmentSuffix[0])->getOrder();
        $shippingMethod = explode('_', $order->getShippingMethod());
        $shippingAddress = $order->getShippingAddress();
        $customerName = explode(' ', $shippingAddress->getName());
        // has to be backwards compatible with older versions of module
        $kpId = ($shippingAddress->getKpId()) ? $shippingAddress->getKpId() : $shippingMethod['2'];

        $orderDetails['orderId'] = 'mag_1.3.0_'.$order->getRealOrderId();
        $orderDetails['kialaPointId'] = $kpId;
        $orderDetails['toCountry'] = $shippingAddress->getCountry();
        $orderDetails['productWeight'] = $order->getWeight();
        $orderDetails['orderDate'] = date('Y-m-d',strtotime($order->getCreatedAt()));
        $orderDetails['customerFirstName'] = $customerName['0'];
        $orderDetails['customerLastName'] = $customerName['1'];
        $orderDetails['street'] = implode('', $shippingAddress->getStreet());
        $orderDetails['postcode'] = $shippingAddress->getPostcode();
        $orderDetails['city'] = $shippingAddress->getCity();
        $orderDetails['email'] = $shippingAddress->getEmail();

        return $orderDetails;
    }

}
