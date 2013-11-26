<?php

    class Kiala_LocateAndSelect_Model_System_Config_Source_RateType {

        /**
         * Options getter
         *
         * @return array
         */
        public function toOptionArray() {
            $options = array(
                array('value' => 0, 'label'=>Mage::helper('locateandselect')->__('Flat Rate')),
                array('value' => 1, 'label'=>Mage::helper('locateandselect')->__('Table Rates')),
            );
            // extra option is available only when modules are enabled
            if (Mage::helper('core')->isModuleEnabled('Owebia_Shipping2')
                && Mage::helper('core')->isModuleEnabled('Kiala_OwebiaShipping2')) {
                $options[] = array('value' => 2, 'label'=>Mage::helper('locateandselect')->__('Kiala Owebia Shipping - Rates Calculation'));
            }
            return $options;
        }
    }