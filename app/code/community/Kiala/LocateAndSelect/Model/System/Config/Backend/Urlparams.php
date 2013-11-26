<?php

class Kiala_LocateAndSelect_Model_System_Config_Backend_Urlparams extends Mage_Core_Model_Config_Data
{

    public function _beforeSave() {
        $values = $this->getValue();
        foreach ($values as $key => $value) {
            if ($value['delete'] == 1) {
                unset($values[$key]);
            }
        }
        $this->setValue(serialize($values));
    }
}