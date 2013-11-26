<?php

/**
 * @package 	Kiala_LocateAndSelect
 * @copyright   Copyright (c) 2012 Kiala
 * @author 		Phpro (http://www.phpro.be)
 */
class Kiala_LocateAndSelect_Model_KialaPoint extends Mage_Core_Model_Abstract
{
    private $_name;
    private $_id;
    private $_shortId;
    private $_street;
    private $_postcode;
    private $_city;
    private $_locationHint;
    private $_pictureUrl;
    private $_available;
    private $_status;
    private $_previousKialaPoint;
    private $_methodDescription = "Based on your address details, we suggest following Kiala point";

    public function __construct($kp)
    {
        $this->_id = (string) $kp->attributes()->id;
        $this->_shortId = (string) $kp->attributes()->shortId;
        $this->_name = (string) $kp->name;
        $this->_street = (string) $kp->address->street;
        $this->_postcode = (string) $kp->address->zip;
        $this->_city = (string) $kp->address->city;
        $this->_locationHint = (string) $kp->address->locationHint;
        $this->_pictureUrl = (isset($kp->picture)) ? (string) str_replace("http://", "https://", $kp->picture->attributes()->href) : Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_SKIN) . 'frontend/base/default/images/kiala/default_picture.jpg';
        $this->_available = (string) $kp->status->attributes()->available;
        $this->_status = (string) $kp->status;
    }

    public function setPreviousKialaPoint($previousKialaPoint)
    {
        $this->_previousKialaPoint = $previousKialaPoint;
    }

    public function getPreviousKialaPoint()
    {
        return $this->_previousKialaPoint;
    }

    public function setMethodDescription($description)
    {
        $this->_methodDescription = $description;
    }

    public function getMethodDescription()
    {
        return $this->_methodDescription;
    }

    public function getId()
    {
        return $this->_id;
    }

    public function getShortId()
    {
        return $this->_shortId;
    }

    public function getName()
    {
        return $this->_name;
    }

    public function getStreet()
    {
        return $this->_street;
    }

    public function getPostcode()
    {
        return $this->_postcode;
    }

    public function getCity()
    {
        return $this->_city;
    }

    public function getLocationHint()
    {
        return $this->_locationHint;
    }

    public function getPictureUrl()
    {
        return $this->_pictureUrl;
    }

    public function getStatus()
    {
        return $this->_status;
    }

    public function isAvailable()
    {
        return $this->_available;
    }

    public function getMethod()
    {
        return "kiala";
    }

    public function getCode()
    {
        return "kiala_kiala";
    }

}
