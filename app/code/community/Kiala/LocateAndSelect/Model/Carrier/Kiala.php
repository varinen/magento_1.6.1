<?php

/**
 * @package 	Kiala_LocateAndSelect
 * @copyright   Copyright (c) 2012 Kiala
 * @author 		Phpro (http://www.phpro.be)
 */
class Kiala_LocateAndSelect_Model_Carrier_Kiala extends Mage_Shipping_Model_Carrier_Tablerate
{
    /**
     * unique internal shipping method identifier
     * @var string [a-z0-9_]
     */
    protected $_code = 'kiala';

    /**
     * Kiala Helper
     * @var Kiala_LocateAndSelect_Helper_Data  
     */
    protected $_kialaHelper;

    /**
     * Getter for carrier code
     * needed for Magento version under 1.4
     *
     * @return string
     */
    public function getCarrierCode() {
        return $this->_code;
    }

    /**
     * Use flatrate configData when parameter does not exsists for Kiala
     *
     * @param   string $field
     * @return  mixed
     */
    public function getConfigData($field) {
        if (empty($this->_code)) {
            return false;
        }
        $data = parent::getConfigData($field);
        if (!$data && $this->kialaHelper()->useTableRates()) {
            $path = 'carriers/tablerate/' . $field;
            $data = Mage::getStoreConfig($path, $this->getStore());
            Mage::Log("ConfigData for [$field] not found on Kiala, Using tablerate configdata ($data)", Zend_Log::INFO, 'kiala.log');
        }
        return $data;
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
     * Use flatrate configFlag when parameter does not exsists for Kiala
     *
     * @param   string $field
     * @return  mixed
     */
    public function getConfigFlag($field) {

        if (empty($this->_code)) {
            return false;
        }

        if (!$this->kialaHelper()->isActive()) {
            return false;
        } else {
            if ($field == "active") {
                return true;
            }
        }

        $data = parent::getStoreConfigFlag($field);
        if (!$data || $data == "") {
            Mage::Log("ConfigFlag for [$field] not found on $this->_code, Using tablerate configdata ", Zend_Log::INFO, 'kiala.log');
            $path = 'carriers/tablerate/' . $field;
            $data = Mage::getStoreConfigFlag($path, $this->getStore());
        }
        return $data;
    }

    /**
     * Collect rates via flat rate, the original table rates or owebia shipping rates and adapt to Kiala
     *
     * @param Mage_Shipping_Model_Rate_Request $request
     *
     * @internal param \Mage_Shipping_Model_Rate_Request $data
     * @return Mage_Shipping_Model_Rate_Result
     */
    public function collectRates(Mage_Shipping_Model_Rate_Request $request) {

        $config_error = false;

        if (!$this->kialaHelper()->isActive()) {
            return false;
        }

        if ($this->kialaHelper()->useTablerates()) {
            $result = parent::collectRates($request);
            if (!$result) {
                return false;
            }
            $method = current($result->getRatesByCarrier('tablerate'));
            if (!$method) {
                Mage::Log("Tablerate not initialised. To add shipping costs, configure magento's standard Tabelrate  shipping method without activating it.", Zend_Log::INFO, 'kiala.log');
                $method = Mage::getModel('shipping/rate_result_method');
            } else {
                Mage::Log("Reset Tablerate  Rate_Results", Zend_Log::INFO, 'kiala.log');
                $result->reset();
            }
        } else if($this->kialaHelper()->useOwebiaRates()) {
            $result = Mage::getModel('kiala_owebia_shipping2/carrier_owebiaKiala')->collectRates($request);
            if (!$result) {
                return false;
            }
            $method = current($result->getRatesByCarrier('owebia_kiala'));
            if (!$method) {
                $method = Mage::getModel('shipping/rate_result_method');
                $config_error = true;
            }
            $result->reset();
        } else {
            Mage::Log("Table Rates disabled.", Zend_Log::INFO, 'kiala.log');
            $result = Mage::getModel('shipping/rate_result');
            $method = Mage::getModel('shipping/rate_result_method');
            $price = $this->kialaHelper()->getFlatFeeShippingCost();
            $method->setPrice($price);
            $method->setCost($price);
        }
        
        $shippingAddress = Mage::getSingleton("checkout/session")->getQuote()->getShippingAddress();
        $country = ($shippingAddress) ? $shippingAddress->getCountry() : null;
        $dspid = $this->kialaHelper()->getDSPIDForDestination($country);
        if ($dspid) {
            $kialaPoint = $this->getKialaPoint();
            if(!$this->kialaHelper()->useOwebiaRates()) {
                if ($kialaPoint) {
                    Mage::Log("Updating shipping method with KialaPoint details", Zend_Log::INFO, 'kiala.log');
                    $method->setMethod($kialaPoint->getMethod());
                    $method->setMethodDescription($kialaPoint->getMethodDescription());
                } else {
                    $method->setMethod('kiala');
                    $method->setMethodDescription($this->kialaHelper()->__('Kiala allows parcels to be delivered to close by shops with wide opening hours and no waiting lines.'));
                }
                $method->setCarrierTitle($this->getConfigData('title'));
                $method->setMethodTitle($this->kialaHelper()->__('Collection Point Delivery'));
            }
            $method->setCarrier('kiala');
            if ($config_error) {
                $method->setMethodTitle($this->kialaHelper()->__('Could not calculate shipping rate'));
            }
            $result->append($method);
        }
        return $result;
    }

    /**
     * Retrieve the nearest KialaPoint
     * @return Kiala_LocateAndSelect_Model_KialaPoint
     */
    protected function getKialaPoint() {
        $kialaPoint = null;
        $locateAndSelect = Mage::getModel("locateandselect/LocateAndSelect");
        $kialaPoints = $locateAndSelect->getKialaPointsForCurrentCheckoutSession(1);
        if (isset($kialaPoints[0])) {
            $kialaPoint = $kialaPoints[0];
        }
        return $kialaPoint;
    }

    /**
     *
     * Method to tell magento this shipping method uses tracking
     * 
     * @return array $allowedShippingMethods
     */
    public function isTrackingAvailable() {
        return true;
    }

    /**
     * 
     * Get tracking result object
     * @param string $tracking_number
     * @return Mage_Shipping_Model_Tracking_Result $tracking_result
     */
    public function getTrackingInfo($tracking_number, $destinationCountry, $order) {
        $tracking_result = $this->getTracking($tracking_number, $destinationCountry, $order);

        if ($tracking_result instanceof Mage_Shipping_Model_Tracking_Result) {
            $trackings = $tracking_result->getAllTrackings();
            if (is_array($trackings) && count($trackings) > 0) {
                return $trackings[0];
            }
        }
        // @codeCoverageIgnoreStart
        return false;
        // @codeCoverageIgnoreEnd
    }

    /**
     * 
     * Get track & trace url 
     * @param string $tracking_number
     * @return Mage_Shipping_Model_Tracking_Result
     */
    public function getTracking($tracking_number, $destinationCountry = null, $order = null) {
        $tracking_result = Mage::getModel('shipping/tracking_result');
        $tracking_status = Mage::getModel('shipping/tracking_result_status');
        $tracking_status->setCarrier($this->_code);
        $tracking_status->setCarrierTitle('<img height="50" alt="Kiala" src="' . Mage::getDesign()->getSkinUrl('images/kiala/locateandselect/kiala-logo.jpg') . '" />');
        $tracking_status->setTracking($tracking_number);

        $url = $this->buildTrackingUrl($tracking_number, $destinationCountry, $order);
        $tracking_status->addData(
                array(
                    'status' => '<a target="_blank" href="' . $url . '">' . Mage::helper('shipping')->__('Track this Parcel') . '</a>'
                )
        );
        $tracking_result->append($tracking_status);

        return $tracking_result;
    }

    public function buildTrackingUrl($tracking_number, $destinationCountry, $order) {
        $dspid = Mage::getModel("locateandselect/dspid");
        $dspid->_init($this->getConfigData('dspid_matrix'));
        $calculatedDspid = isset($order) ? $order->getDspid() : $dspid->getDSPIDForDestination($this->getConfigData('sender_country'), $destinationCountry);
        $language = isset($order) ? $order->getLanguage() : $this->kialaHelper()->getStoreLang();

        $parameters = array(
            'countryid' => $destinationCountry,
            'language' => $language,
            'dspid' => $calculatedDspid,
            'dspparcelid' => $tracking_number,
        );

        return $this->kialaHelper('url')->getTrackAndTraceUri($parameters);
    }

}
