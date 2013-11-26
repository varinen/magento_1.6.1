<?php

/**
 * @group Kiala
 * @group carrierKiala
 */
class Kiala_LocateAndSelect_Test_Model_Carrier_Kiala extends EcomDev_PHPUnit_Test_Case {

    private $_carrier;

    public function setUp() {
        parent::setUp();
        $this->_carrier = Mage::getModel("locateandselect/carrier_kiala");
    }

    /**
     *
     * @test
     * @loadFixture kialaConfig.yaml
     * @group getCarrierCode
     * @doNotIndexAll
     */
    public function getCarrierCodeReturnsStringKiala() {
        $this->assertEquals("kiala", $this->_carrier->getCarrierCode());
    }

    /**
     *
     * @test
     * @loadFixture kialaInactiveConfig.yaml
     * @group collectRates
     * @doNotIndexAll
     */
    public function collectRatesReturnsFalseWhenLocateAndSelectIsNotEnabled() {
        $this->assertFalse($this->_carrier->collectRates($request));
    }
  
    /**
     *
     * @todo
     * @loadFixture kialaConfig.yaml
     * @group collectRates
     * @group wip
     * @doNotIndexAll
     */
    public function collectRatesReturnsRateResultObjectWithOneRateResultMethod() {      

        $locateAndSelectMock = $this->getModelMock('locateandselect/LocateAndSelect',array('getKialaPointsForCurrentCheckoutSession'));
        $kp = $locateAndSelectMock->transformXmlToKialaPoints($this->getXml());
        $locateAndSelectMock->expects($this->any())
                     ->method("getKialaPointsForCurrentCheckoutSession")
                     ->will($this->returnValue($kp));
        $this->replaceByMock('model', 'locateandselect/LocateAndSelect', $locateAndSelectMock);

        $tablerateMock = $this->getModelMock('shipping/Carrier_Tablerate',array('collectRates'));
        $tablerateMock->expects($this->once())
                     ->method("collectRates")
                     ->will($this->returnValue("test"));        
        $this->replaceByMock('model', 'shipping/Carrier_Tablerate', $tablerateMock);
        
        $kialaMock = $this->getModelMock('locateandselect/Carrier_Kiala',array("getKialaPoint"));
        $kialaMock->expects($this->any())
                 ->method("getKialaPoint")
                 ->will($this->returnValue($kp));
                     
        $rateResult = $kialaMock->collectRates($request);
        $this->assertInstanceOf("Mage_Shipping_Model_Rate_Result", $rateResult);
 
        $rates      = $rateResult->getAllRates();
        $this->assertEquals(1, count($rates));
 
        $rate       = $rates[0];
        $this->assertEquals("Kiala", $rate->getCarrierTitle());
        $this->assertEquals("kiala", $rate->getCarrier());
        $this->assertEquals("kiala_0210", $rate->getMethod());
        $this->assertEquals("UPSELL", $rate->getMethodTitle());
        $this->assertEquals(5, $rate->getPrice());
        $this->assertEquals(2, $rate->getCost());

    }



    public function getXml() {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<?xml-stylesheet href="/static/style/kplist/readable.css" type="text/css"?>
<kplist xmlns="http://locateandselect.kiala.com/schema/kplist"
 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
 xsi:schemaLocation="http://locateandselect.kiala.com/schema/kplist http://locateandselect.kiala.com/schema/kplist-1.1.xsd"
 xml:lang="nl" dsp="DEMO_DSP" country="BE" zip="2550" date="2012-02-06T13:44:42+0100" delay="2">
 <kp id="32010210" shortId="0210">
  <name>UPSELL</name>
  <address>
   <street>Veldkant, 39</street>
   <zip>2550</zip>
   <city>Kontich</city>
   <locationHint>Dicht bij uitrit van de E19, op het industrieterrein.</locationHint>
  </address>
   <remark>Betalen kan contant of met Bancontact</remark>
  <status available="1" code="ACTIVE"/>
   <openingHours>
    <day name="MON"><timespan><start>09:00</start><end>12:00</end></timespan><timespan><start>13:00</start><end>18:00</end></timespan></day>
    <day name="TUE"><timespan><start>09:00</start><end>12:00</end></timespan><timespan><start>13:00</start><end>18:00</end></timespan></day>
    <day name="WED"><timespan><start>09:00</start><end>12:00</end></timespan><timespan><start>13:00</start><end>18:00</end></timespan></day>
    <day name="THU"><timespan><start>09:00</start><end>12:00</end></timespan><timespan><start>13:00</start><end>18:00</end></timespan></day>
    <day name="FRI"><timespan><start>09:00</start><end>12:00</end></timespan><timespan><start>13:00</start><end>18:00</end></timespan></day>
    <day name="SAT"/>
    <day name="SUN"/>
   </openingHours>
   <picture href="http://locateandselect.kiala.com/kpimages/be/32010210.jpg"/>
   <coordinate><latitude>51.1405665</latitude><longitude>4.4413845</longitude></coordinate>
   <label>
    <tag name="name">UPSELL</tag>
   </label>
 </kp>
</kplist>
XML;
        
        return $xml;
    }
}
