<?php

class Kiala_LocateAndSelect_Model_System_Config_Source_PackAndShip {

    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray() {
        return array(
            array('value' => 1, 'label'=>Mage::helper('locateandselect')->__('Pack&Ship Desktop')),
            array('value' => 0, 'label'=>Mage::helper('locateandselect')->__('Pack&Ship WS')),
            array('value' => 2, 'label'=>Mage::helper('locateandselect')->__('UPS Worldship Export')),
        );
    }

}
