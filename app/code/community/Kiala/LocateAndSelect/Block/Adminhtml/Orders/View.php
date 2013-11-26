<?php

class Kiala_LocateAndSelect_Block_Adminhtml_Orders_View extends Mage_Adminhtml_Block_Sales_Order_View
{

    public function __construct()
    {
        $order = $this->getOrder();
        if (Mage::helper('locateandselect')->isKialaShippingMethod($order->getShippingMethod())) {
            $this->addButton('kiala_back', array(
                'label' => 'Back to Kiala',
                'onclick' => "setLocation('" . $this->getUrl('adminhtml/kiala_index/index') . "')",
                'class' => 'back'
            ));
        }

        parent::__construct();
    }

}
