<?php
    class Kiala_OwebiaShipping2_Model_Carrier_OwebiaKiala extends Owebia_Shipping2_Model_Carrier_AbstractOwebiaShipping
    {
        protected $_code = 'owebia_kiala';

        public function collectRates(Mage_Shipping_Model_Rate_Request $request) {
            $process = $this->__getProcess($request);
            $rates = $this->getRates($process);

            if(!$rates->getAllRates()){
                Mage::Log("Could not calculate shipping rate. Please, check your configuration in System / Configuration / Shipping Methods / Kiala Owebia Shipping - Rates Calculation",
                    Zend_Log::INFO, 'kiala.log');
            }

            return $rates;
        }
    }