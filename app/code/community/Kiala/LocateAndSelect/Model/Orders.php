<?php

/**
 * @package 	Kiala_LocateAndSelect
 * @copyright   Copyright (c) 2012 Kiala
 * @author 		Phpro (http://www.phpro.be)
 */
class Kiala_LocateAndSelect_Model_Orders extends Mage_Core_Model_Abstract
{
    protected $_kialaHelper;

    protected function _construct() {
        $this->_init('locateandselect/orders');
    }

    /**
     * Method for accessing the module's helper in this class.
     * @return object $helper
     */
    public function kialaHelper() {
        if (is_null($this->_kialaHelper)) {
            $this->_kialaHelper = Mage::helper('locateandselect');
        }
        return $this->_kialaHelper;
    }

    /**
     * Gets the order collection, filtered for Kiala orders which aren't canceled.
     * @param array $orderIds
     * @return object $collection
     */
    public function getCollection($orderIds = false) {
        Mage::log('Getting collection of Kiala orders.', Zend_Log::INFO, 'kiala.log');
        // get a collection of only kiala orders
        $billingAliasName = 'billing_o_a';
        $shippingAliasName = 'shipping_o_a';
        $joinTable = Mage::getSingleton('core/resource')->getTableName('sales/order_address');

        $orderModel = Mage::getModel('sales/order');
        $collection = $orderModel->getCollection()
                ->addFieldToFilter('status', array('neq' => 'canceled'))
                ->addAttributeToFilter('shipping_method', array('like' => 'kiala%'));
        $collection->getSelect()->joinLeft(
                        array($billingAliasName => $joinTable), "(main_table.entity_id = $billingAliasName.parent_id AND $billingAliasName.address_type = 'billing')", array(
                    "CONCAT_WS(' ', $billingAliasName.firstname, $billingAliasName.lastname) AS billing_name"
                ))
                ->joinLeft(
                        array($shippingAliasName => $joinTable), "(main_table.entity_id = $shippingAliasName.parent_id AND $shippingAliasName.address_type = 'shipping')", array(
                    $shippingAliasName . '.company'
                ));
        if (is_array($orderIds)) {
            $collection->addAttributeToFilter('entity_id', array('in' => $orderIds));
        }
                        
        return $collection;
    }

    /**
     * Gets the previous Kiala order.
     * @param integer $customerId
     * @return object
     */
    public function getPreviousKialaOrder($customerId, $country) {
        // get a collection of only kiala orders
        $orderModel = Mage::getModel('sales/order');
        $collection = $orderModel->getCollection()
                ->addAttributeToFilter('customer_id', array('eq' => $customerId))
                ->addAttributeToFilter('shipping_method', array('like' => 'kiala%'))
                ->addAttributeToSort('created_at', 'DESC');

        $previousOrders = $collection->load();
        foreach ($previousOrders as $order) {
            $previousCountry = $order->getShippingAddress()->getCountry();
            if ($previousCountry == $country) {
                return $order;
            }
        }
        return null;
    }

    /**
     * Builds the string for the csv export, and saves the file to disk.
     *
     * @param string $filename
     * @param bool $ups
     * @param null $orderIds
     *
     * @return mixed
     */
    public function toCsv($filename, $ups, $orderIds = null) {
        Mage::log('Generating CSV.', Zend_Log::INFO, 'kiala.log');
        $collection = $this->getCollection($orderIds);

        $error = array();
        $csvArray = array();
        
        foreach ($collection as $order) {
            if ($order->getKialaExported()) {
                continue;
            }
            
            $orderId = $order->getRealOrderId();
            $country = $order->getShippingAddress()->getCountry();

            if ($dspid=$order->getDspid() == '') {
                $dspid = $this->kialaHelper()->getDSPIDForDestination($country);
                $order->setDspid($dspid)->save();
            }

            if ($order->hasShipments()) {
                $shipments = $order->getShipmentsCollection();
                foreach ($shipments as $shipment) {
                    $shipmentId = $shipment->getIncrementId();
                    $trackingShipmentId = $shipmentId;
                    $this->addTrackingCodeToShipment($shipmentId, false);
                    foreach ($shipment->getAlltracks() as $trackingCode) {
                        if ($trackingCode->getCarrierCode() == 'kiala' && $trackingCode->getTitle() == 'Desktop') {
                            $csvArray[] = $this->_getCsvLine($order, $trackingCode->getNumber(), $shipmentId, $ups);
                            
                            $trackingShipmentId = $this->incrementSuffix($trackingShipmentId);
                        }
                    }
                }
            } else {
                if ($order->canShip()) {
                    $shipmentId = $this->createShipment($orderId);
                    $this->addTrackingCodeToShipment($shipmentId, false);
                    $shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId($shipmentId);
                    foreach ($shipment->getAlltracks() as $trackingCode) {
                        if ($trackingCode->getCarrierCode() == 'kiala') {
                            $csvArray[] = $this->_getCsvLine($order, $trackingCode->getNumber(), $shipmentId, $ups);
                        }
                    }
                }
            }
            $order->addStatusHistoryComment('', 'complete');
            $order->setKialaExported(true)
                    ->setStatus('complete', true)
                    ->save();
            $shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId($shipmentId);
            $shipment->sendEmail();
        }
        
        Mage::log("Done generating CSV. Saving to disk now.", Zend_Log::INFO, 'kiala.log');

        if(count($csvArray)) {
            $result['csv'] = true;
            // Add header line to UPS csv export file
            if ($ups) {
                array_unshift($csvArray, $this->_getCsvHeader());
            }
            $this->_writeCsvFile($filename, $csvArray);
        } else {
            $result['csv'] = false;
            Mage::log('Nothing to export.', Zend_Log::INFO, 'kiala.log');
        }
        
        
        $result['error'] = $error;
        return $result;
    }

    /**
     * Sub-method of toCsv(), adds 1 line to the csv string.
     *
     * @param object    $order
     * @param           $parcelNumber
     * @param integer   $shipmentId
     * @param bool      $ups
     *
     * @return array|null
     */
    protected function _getCsvLine($order, $parcelNumber, $shipmentId, $ups) {
        $orderId = $order->getRealOrderId();
        
        $address = $order->getBillingAddress();
        $kpAddress = $order->getShippingAddress();

        // has to be backwards compatible with older versions of module
        $kpid = sprintf('%05d', ($kpAddress->hasKpId() ? $kpAddress->getKpId() : $this->kialaHelper()
                ->getShortIdFromShippingMethodName($order->getShippingMethod())));
        $storeId = $order->getStoreId();
        $fromCountry = Mage::getStoreConfig('carriers/kiala/sender_country', $storeId);
        $dspid = ($order->getDspid() != '') ? $order->getDspid() : Mage::getModel('locateandselect/dspid')->getDSPIDForDestination($fromCountry, $address->getCountryId());
        
        $language = ($order->getLanguage() != '') ? $order->getLanguage() : $this->kialaHelper()->getStoreLang();

        if ($dspid == null) {
            Mage::log('Order ' . $orderId . ' cannot be exported. Please check your DSPID configuration if the destination country is allowed.', Zend_Log::INFO, 'kiala.log');
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('locateandselect')->__('Could not export the following order:') . ' ' . $orderId);
            return null;
        }

        $returnArray = array(
            $dspid,//partnerId
            '',//$dspid partnerBarcode
            $parcelNumber,//parcelNumber
            $orderId,//orderNumber
            date('Ymd', strtotime($order->getCreatedAtDate())),//orderDate
            '',//invoiceNumber
            '',//invoiceDate
            $shipmentId,//shipmentNumber
            '0.00',//CODAmount
            sprintf("%01.2f", round($order->getGrandTotal(), 2)),//commercialValue
            sprintf("%.3f", $order->getWeight()),//parcelWeight
            '',//parcelVolume
            'mag_1.3.0',//parcelDescription
            $order->getCustomerEmail(),//customerId
            $address->getLastname(),//customerName
            $address->getFirstname(),//customerFirstName
            '',//customerTitle
            $address->getStreet(1),//customerStreet
            $address->getStreet(2),//customerStreetNumber
            $address->getStreet(3),//customerExtraAddressLine
            $address->getPostcode(),//customerZip
            $address->getCity(),//customerCity
            $address->getCountryId(),//customerLocality
            $language,//customerLanguage
            $address->getTelephone(),//customerPhone1
            '',//customerPhone2
            '',//customerPhone3
            $order->getCustomerEmail(),//customerEmail1
            '',//customerEmail2
            '',//customerEmail3
            'Y',//positiveNotificationRequested
            $kpid,//kialaPoint
            '',//backupKialaPoint
        );

        // Extra fields for UPS csv export
        if ($ups) {
            array_push($returnArray,
                $kpAddress->getCompany(), //APstoreName
                $kpAddress->getStreet(1), //APaddress
                $kpAddress->getCity(), //APcity
                $kpAddress->getPostcode(), //APpostalcode
                $kpAddress->getCountryId(), //APcountry
                '' //ConsigneeHouseNumber
            );
        }

        array_push($returnArray, ''); // extra field for closing |
        
        Mage::log('Added line to CSV.', Zend_Log::INFO, 'kiala.log');
        return $returnArray;
    }

    /**
     * Header line for UPS csv file
     *
     * @return array
     */
    private function _getCsvHeader()
    {
        return array('partnerId','partnerBarcode','parcelNumber','orderNumber','orderDate','invoiceNumber',
                     'invoiceDate','shipmentNumber','CODAmount','commercialValue','parcelWeight','parcelVolume',
                     'parcelDescription','customerId','customerName','customerFirstName','customerTitle',
                     'customerStreet','customerStreetNumber','customerExtraAddressLine','customerZip','customerCity',
                     'customerLocality','customerLanguage','customerPhone1','customerPhone2','customerPhone3',
                     'customerEmail1','customerEmail2','customerEmail3','positiveNotificationRequested','kialaPoint',
                     'backupKialaPoint','APstoreName','APaddress','APcity','APpostalcode','APcountry',
                     'ConsigneeHouseNumber','');
    }

    /**
     * Sub-method of toCsv(), saves the csv string in a file on the disk.
     *
     * @param string $filename
     * @param array $csvArray
     */
    protected function _writeCsvFile($filename, $csvArray) {
        $io = new Varien_Io_File();

        $path = Mage::getBaseDir('var').DS.'export'.DS.'kiala'.DS;
        $file = $path.$filename;
        
        $io->setAllowCreateFolders(true);
        $io->open(array('path' => $path));
        $io->streamOpen($file, 'w+');
        $io->streamLock(true);
        
        foreach($csvArray as $csvLine) {
            $io->streamWriteCsv($csvLine, '|');
        }
        
        $io->streamUnlock();
        $io->streamClose();
    }

    /**
     * Creates tracking codes for the selected orders from the Kiala PackAndShip webservice.
     * @param array $orderIds
     * @return mixed String when success or complete failure, array of orderIds when partial failure.
     */
    public function createTrackingCode($orderIds) {
        Mage::log('Creating tracking code(s)', Zend_Log::INFO, 'kiala.log');

        $results = array();
        $collection = $this->getCollection($orderIds);
        $shipment_api = Mage::getModel('sales/order_shipment_api');
        $packAndShip = Mage::getModel('locateandselect/PackAndShip');

        foreach ($collection as $order) {
            $orderId = $order->getRealOrderId();
            if ($order->hasShipments()) {
                $shipments = $order->getShipmentsCollection();
                foreach ($shipments as $shipment) {
                    $shipmentId = $shipment->getIncrementId();
                    $results[$orderId] = $this->addTrackingCodeToShipment($shipmentId, true);
                }
            } else {
                if ($order->canShip()) {
                    $shipmentId = $this->createShipment($orderId);
                    $results[$orderId] = $this->addTrackingCodeToShipment($shipmentId, true);
                }
            }
            if ($results[$orderId] == true) {
                $order->setKialaExported(true)->setStatus('complete', true)->save();
                $shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId($shipmentId);
                $shipment->sendEmail();
            }
        }

        if (in_array(true, $results) && in_array(false, $results)) {
            return $results;
        } else if (in_array(true, $results)) {
            return 'success';
        }

        return 'fail';
    }

    /**
     * Adds the tracking code(s) to the shipment.
     * @param integer $shipmentId
     * @param boolean $packandship
     * @return boolean Success
     */
    public function addTrackingCodeToShipment($shipmentId, $packandship = false) {
        Mage::log("Adding tracking code to shipment $shipmentId.", Zend_Log::INFO, 'kiala.log');
        $shipment = Mage::getModel('sales/order_shipment')->loadByIncrementId($shipmentId);
        $order = $shipment->getOrder();
        $orderId = $order->getRealOrderId();
        $storeId = $shipment->getOrder()->getStoreId();
        $fromCountry = Mage::getStoreConfig('carriers/kiala/sender_country', $storeId);
        $toCountry = $shipment->getShippingAddress()->getCountry();
        $trackingCodes = $shipment->getAllTracks();
        $packAndShipModel = Mage::getModel('locateandselect/PackAndShip');
        $shipment_api = Mage::getModel('sales/order_shipment_api');
        $carrierTitle = ($packandship) ? 'Kiala' : 'Desktop';
        $success = false;

        if (empty($trackingCodes)) {
            try {
                $trackingNumber = ($packandship) ? $packAndShipModel->getTrackingCodeFromKiala($shipmentId) : Mage::getModel('locateandselect/dspid')->getDSPIDForDestination($fromCountry, $toCountry) . $shipmentId;
                if ($trackingNumber) {
                    $shipment_api->addTrack($shipmentId, 'kiala', $carrierTitle, $trackingNumber);
                    Mage::log("Added trackingnumber $trackingNumber to shipment $shipmentId.", Zend_Log::INFO, 'kiala.log');
                    $success = true;
                }
            } catch (Exception $e) {
                Mage::log($e->getMessage(), Zend_Log::ERR, 'kiala.log');
                $success = false;
            }
        } else {
            try {
                $trackingShipmentId = $shipmentId;
                foreach ($trackingCodes as $trackingCode) {
                    if ($trackingCode->getCarrierCode() == 'kiala' && strtolower($trackingCode->getNumber()) == 'x') {
                        $trackingNumber = ($packandship) ? $packAndShipModel->getTrackingCodeFromKiala($trackingShipmentId) : Mage::getModel('locateandselect/dspid')->getDSPIDForDestination($fromCountry, $toCountry) . $trackingShipmentId;
                        if ($trackingNumber) {
                            $shipment_api->removeTrack($shipmentId, $trackingCode->getId());
                            $shipment_api->addTrack($shipmentId, 'kiala', $carrierTitle, $trackingNumber);
                            Mage::log("Updated trackingnumber(s) of shipment $shipmentId.", Zend_Log::INFO, 'kiala.log');
                            $success = true;
                        }
                        $orderId = $this->incrementSuffix($orderId);
                        $trackingShipmentId = $this->incrementSuffix($trackingShipmentId);
                    } else {
                        Mage::log('Tracking code(s) already requested, or not of carrier Kiala', Zend_Log::INFO, 'kiala.log');
                    }
                }
            } catch (Exception $e) {
                Mage::log($e->getMessage(), Zend_Log::ERR, 'kiala.log');
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Creates a shipment, necessary for adding tracking codes and exporting.
     * @param integer $orderId
     * @return integer $shipmentId
     */
    public function createShipment($orderId) {
        Mage::log('Creating shipment for order ' . $orderId, Zend_Log::INFO, 'kiala.log');
        //load order by increment id
        $order = Mage::getModel("sales/order")->loadByIncrementId($orderId);
        // so we return false in case of error, or unshippable
        $shipmentId = false;
        try {
            if ($order->canShip()) {
                //Create shipment
                $itemQty = $order->getItemsCollection()->count();
                $shipment = Mage::getModel('sales/service_order', $order)->prepareShipment($itemQty);
                $shipment = new Mage_Sales_Model_Order_Shipment_Api();
                $shipmentId = $shipment->create($orderId);
            }
            Mage::log("shipment $shipmentId created", Zend_Log::INFO, 'kiala.log');
        } catch (Mage_Core_Exception $e) {
            Mage::log($e->getMessage(), Zend_Log::ERR, 'kiala.log');
        }
        return $shipmentId;
    }

    private function incrementSuffix($id) {
        $suffix = explode('_', $id);

        if (!isset($suffix[1])) {
            return $id . '_2';
        }

        $suffix[1] += 1;
        $id = implode('_', $suffix);

        return $id;
    }

    /* ==================================
      Replaces special characters with non-special equivalents
      ================================== */

    private function normalize_special_characters($str) {
        $invalid = array('Š' => 'S', 'š' => 's', 'Đ' => 'Dj', 'đ' => 'dj', 'Ž' => 'Z', 'ž' => 'z',
            'Č' => 'C', 'č' => 'c', 'Ć' => 'C', 'ć' => 'c', 'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A',
            'Ä' => 'A', 'Å' => 'A', 'Æ' => 'A', 'Ç' => 'C', 'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
            'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O',
            'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ý' => 'Y',
            'Þ' => 'B', 'ß' => 'Ss', 'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a',
            'æ' => 'a', 'ç' => 'c', 'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i',
            'î' => 'i', 'ï' => 'i', 'ð' => 'o', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o',
            'ö' => 'o', 'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ý' => 'y', 'ý' => 'y', 'þ' => 'b',
            'ÿ' => 'y', 'Ŕ' => 'R', 'ŕ' => 'r', "`" => "'", "´" => "'", "„" => ",", "`" => "'",
            "´" => "'", "“" => "\"", "”" => "\"", "´" => "'", "&acirc;€™" => "'", "{" => "",
            "~" => "", "–" => "-", "’" => "'");

        $str = str_replace(array_keys($invalid), array_values($invalid), $str);

        return $str;
    }

}
