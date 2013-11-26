<?php

class Kiala_LocateAndSelect_Block_Adminhtml_Widget_Grid_Column_Renderer_Items extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    public function render(Varien_Object $row)
    {
        $itemcount = 0;
        
        $value = $row->getData($this->getColumn()->getIndex());

        $order = Mage::getModel('sales/order')->loadByIncrementId($value);
        $items = $order->getAllVisibleItems();
        foreach ($items as $item) {
            $itemcount += $item->getQtyOrdered();
        }

        return $itemcount;
    }

}