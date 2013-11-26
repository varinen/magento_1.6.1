<?php
class Kiala_LocateAndSelect_Block_Checkout_Onepage_Shipping_Method_Kiala_Kialapoint extends Kiala_LocateAndSelect_Block_Checkout_Onepage_Shipping_Method_Kiala {
    public function canShowSelectButton() {
        if(!Mage::helper('locateandselect')->kialaPointChosen()) {
            return true;
        } elseif ($this->getIsChange()) {
            return true;
        } else {
            return false;
        }
    }
}