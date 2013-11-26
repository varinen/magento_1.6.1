<?php

class Kiala_LocateAndSelect_Test_Model_Orders extends EcomDev_PHPUnit_Test_Case {

    /**
     * @test
     * @group kialaOrders
     * @loadFixture orderConfig.yaml
     * @doNotIndexAll
     */
    public function collectionHasOnlyKialaOrders() {
        $model = Mage::getModel('locateandselect/orders');
        $collection = $model->getCollection();

        foreach ($collection as $product) {
            $this->assertEquals('kiala_kiala_0210', $product->getShippingMethod(), 'Shipping Method was not as expected!');
        }
    }

    /**
     * @test
     * @group trackingCode
     * @loadFixture orderConfig.yaml
     * @doNotIndexAll
     */
    public function trackingCodeIsAddedToShipment() {
        $shipmentMock = $this->getModelMock('sales/order_shipment', array('getData'));
        $shipmentMock->expects($this->any())
                ->method('getData');
        $shipmentsCollection = array($shipmentMock);

        $orderMock = $this->getModelMock('sales/order', array('getId', 'hasShipments', 'getShipmentsCollection'));
        $orderMock->expects($this->any())
                ->method('getId')
                ->will($this->returnValue('5'));
        $orderMock->expects($this->any())
                ->method('hasShipments')
                ->will($this->returnValue(true));
        $orderMock->expects($this->any())
                ->method('getShipmentsCollection')
                ->will($this->returnValue($shipmentsCollection));
        $orderCollection = array($orderMock);

        $kialaOrdersMock = $this->getModelMock('locateandselect/orders', array('getCollection', 'getTrackingCodeFromKiala'));

        $kialaOrdersMock->expects($this->any())
                ->method('getCollection')
                ->will($this->returnValue($orderCollection));
        $kialaOrdersMock->expects($this->any())
                ->method('getTrackingCodeFromKiala')
                ->will($this->returnValue('unimportant'));

        $trackMock = $this->getModelMock('sales/order_shipment_track', array('save'));
        // What we actually want to test:
        $trackMock->expects($this->once())
                ->method('save');
        
        $this->replaceByMock('model', 'sales/order_shipment_track', $trackMock);

        $kialaOrdersMock->createTrackingCode('unimportant');
    }

}

?>
