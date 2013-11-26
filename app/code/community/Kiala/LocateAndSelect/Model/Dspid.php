<?php

/**
 * @package 	Kiala_LocateAndSelect
 * @copyright   Copyright (c) 2012 Kiala
 * @author 		Phpro (http://www.phpro.be)
 */
class Kiala_LocateAndSelect_Model_Dspid
{
    private $_dpsidMappings = array();

    public function __construct()
    {
        $this->_init();
    }

    public function _init()
    {
        $dspidMappingString = Mage::getStoreConfig('carriers/kiala/dspid_matrix');
        $dspidMappingLines = explode(';', trim(str_replace("\n", "", $dspidMappingString)));
        foreach ($dspidMappingLines as $line)
        {
            $line = trim($line);
            if (!empty($line)) {
                $mapping = explode('=', $line);
                $key = trim($mapping[0]);
                $value = trim($mapping[1]);
                $this->_dpsidMappings[$key] = $value;
            }
        }
    }

    public function getDSPIDForDestination($fromCountry, $destinationCountry)
    {
        $dspid = null;
        if (empty($fromCountry)) {
            return $dspid;
        }
        $key = "$fromCountry-->$destinationCountry";
        if (array_key_exists($key, $this->_dpsidMappings)) {
            $dspid = $this->_dpsidMappings[$key];
            Mage::Log("The dspid from $fromCountry to $destinationCountry is $dspid", Zend_Log::INFO, 'kiala.log');
        } else {
            Mage::Log("No dspid from $fromCountry to $destinationCountry", Zend_Log::INFO, 'kiala.log');
        }
        return $dspid;
    }

}
