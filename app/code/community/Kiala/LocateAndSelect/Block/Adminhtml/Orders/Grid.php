<?php

class Kiala_LocateAndSelect_Block_Adminhtml_Orders_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId('kialaGrid');
        $this->setDefaultSort('created_at');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
        $this->setUseAjax(true);
    }

    protected function _prepareCollection()
    {
        $model = Mage::getModel('locateandselect/orders');
        $collection = $model->getCollection();
        $this->setCollection($collection);

        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {

        $this->addColumn('real_order_id', array(
            'header' => Mage::helper('sales')->__('Order #'),
            'width' => '80px',
            'type' => 'text',
            'index' => 'increment_id',
        ));

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header' => Mage::helper('sales')->__('Purchased From (Store)'),
                'index' => 'store_id',
                'type' => 'store',
                'store_view' => true,
                'display_deleted' => true,
            ));
        }

        $this->addColumn('created_at', array(
            'header' => Mage::helper('sales')->__('Purchased On'),
            'index' => 'created_at',
            'type' => 'datetime',
            'width' => '100px',
        ));     
        
        $this->addColumn('billing_name', array(
            'header' => Mage::helper('sales')->__('Bill to Name'),
            'index' => 'billing_name',
            'type' => 'text',
            'filter_condition_callback' => array($this, '_addOrderBillingNameToFilter'),
        ));

        $this->addColumn('kialapoint', array(
            'header' => Mage::helper('locateandselect')->__('Kialapoint'),
            'index' => 'company',
            'type' => 'text',
            'filter_condition_callback' => array($this, '_addOrderKialapointToFilter'),
        ));

        $this->addColumn('base_grand_total', array(
            'header' => Mage::helper('sales')->__('G.T. (Base)'),
            'index' => 'base_grand_total',
            'type' => 'currency',
            'currency' => 'base_currency_code',
        ));

        $this->addColumn('grand_total', array(
            'header' => Mage::helper('sales')->__('G.T. (Purchased)'),
            'index' => 'grand_total',
            'type' => 'currency',
            'currency' => 'order_currency_code',
        ));

        $this->addColumn('total_item_count', array(
            'header' => Mage::helper('locateandselect')->__('# of items'),
            'index' => 'increment_id',
            'width' => '50px',
            'renderer' => 'Kiala_LocateAndSelect_Block_Adminhtml_Widget_Grid_Column_Renderer_Items'
        ));

        $this->addColumn('status', array(
            'header' => Mage::helper('sales')->__('Status'),
            'index' => 'status',
            'type' => 'options',
            'width' => '70px',
            'options' => Mage::getSingleton('sales/order_config')->getStatuses(),
        ));

        $this->addColumn('kiala_exported', array(
            'header' => Mage::helper('locateandselect')->__('Processed'),
            'index' => 'kiala_exported',
            'type' => 'options',
            'width' => '50px',
            'options' => array(
                '1' => Mage::helper('sales')->__('Yes'),
                '0' => Mage::helper('sales')->__('No')
            )
        ));

        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
            $this->addColumn('action', array(
                'header' => Mage::helper('sales')->__('Action'),
                'width' => '50px',
                'type' => 'action',
                'getter' => 'getId',
                'actions' => array(
                    array(
                        'caption' => Mage::helper('sales')->__('View'),
                        'url' => array('base' => 'adminhtml/sales_order/view'),
                        'field' => 'order_id'
                    )
                ),
                'filter' => false,
                'sortable' => false,
                'index' => 'stores',
                'is_system' => true,
            ));
        }

        if (Mage::helper('locateandselect')->isUpsWorldshipUser()) {
            $this->addExportType('*/*/exportUpsCsv', Mage::helper('locateandselect')->__('UPS Worldship Export'));
        }

        if (Mage::helper('locateandselect')->isDesktopAppUser()) {
            $this->addExportType('*/*/exportCsv', Mage::helper('locateandselect')->__('Kiala export file'));
        }

        return parent::_prepareColumns();
    }

    protected function _prepareMassaction()
    {
        $this->setMassactionIdField('entity_id'); // KR?? entity_id=real_order_id
        $this->getMassactionBlock()->setFormFieldName('order_ids');
        $this->getMassactionBlock()->setUseSelectAll(true);

        if (Mage::helper('locateandselect')->isDesktopAppUser()) {
            $this->getMassactionBlock()->addItem('export', array(
                'label' => Mage::helper('sales')->__('Export selected'),
                'url' => $this->getUrl('*/*/exportCsv'),
            ));
        } else if (Mage::helper('locateandselect')->isUpsWorldshipUser()) {
            $this->getMassactionBlock()->addItem('export', array(
                'label' => Mage::helper('sales')->__('Export selected'),
                'url' => $this->getUrl('*/*/exportUpsCsv'),
            ));
        } else {
            $this->getMassactionBlock()->addItem('tracking', array(
                'label' => Mage::helper('sales')->__('Create tracking code'),
                'url' => $this->getUrl('*/*/trackingCode'),
            ));
        }

        return $this;
    }

    public function getRowUrl($row)
    {
        if (Mage::getSingleton('admin/session')->isAllowed('sales/order/actions/view')) {
            return $this->getUrl('adminhtml/sales_order/view', array('order_id' => $row->getId()));
        }
        return false;
    }

    public function getGridUrl()
    {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }
    
    protected function _addOrderBillingNameToFilter($collection, $column) 
    {
        $joinTable = 'billing_o_a';
        $value = $column->getFilter()->getValue();
        $collection->getSelect()->where("concat_ws(' ',$joinTable.firstname,$joinTable.lastname) LIKE '%{$value}%'");
        
        return $this;
    }
    
    protected function _addOrderKialapointToFilter($collection, $column) 
    {
        $joinTable = 'shipping_o_a';
        $value = $column->getFilter()->getValue();
        $collection->getSelect()->where("$joinTable.company LIKE '%{$value}%'");
                
        return $this;
    }
}