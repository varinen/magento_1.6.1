<?php

/**
 * @group Kiala
 * @group LocateAndSelect
 */
class Kiala_LocateAndSelect_Test_Model_LocateAndSelect extends EcomDev_PHPUnit_Test_Case {

    /**
     * @test
     * @doNotIndexAll
     */
    public function getProxyConfigReturnsEmptyArrayIfThereAreNoProxySettings() {
        $model = Mage::getModel("locateandselect/LocateAndSelect");
        $this->assertEquals(array(), $model->getProxyConfig());
    }

    /**
     * @test
     * @loadFixture proxyConfig.yaml
     * @doNotIndexAll
     */
    public function getProxyConfigReturnsArrayWithProxySettingsIfThereAreProxySettings() {
        $model = Mage::getModel("locateandselect/LocateAndSelect");
        $this->assertEquals(array('adapter' => 'Zend_Http_Client_Adapter_Proxy',
            'proxy_host' => "proxy.iconos.be",
            'proxy_port' => "8080",
                ), $model->getProxyConfig());
    }

    private function getShippingAddressMock($shortId = false) {
        $returnId = ($shortId) ? $shortId : null;
        $shippingAddress = $this->getModelMock('sales/quote_address', array('getStreet', 'getPostcode', 'getCity', 'getCountry', 'getShippingMethod'));
        $shippingAddress->expects($this->any())
                ->method("getStreet")
                ->will($this->returnValue(array("éssayer d'escaper ça strïng corrècté", "33")));
        $shippingAddress->expects($this->any())
                ->method("getPostcode")
                ->will($this->returnValue("2550"));
        $shippingAddress->expects($this->any())
                ->method("getCity")
                ->will($this->returnValue("Vrèsse-sur-mèsûre"));
        $shippingAddress->expects($this->any())
                ->method('getCountry')
                ->will($this->returnValue('be'));
        $shippingAddress->expects($this->any())
                ->method('getShippingMethod')
                ->will($this->returnValue($returnId));

        return $shippingAddress;
    }

    /**
     * @test
     * @loadFixture kialaConfig.yaml
     * @group locateNearestKialaPoint
     * @doNotIndexAll
     */
    public function locateNearestKialaPoint() {
        $uri = "http://locateandselect.kiala.com/kplist?dspid=DEMO_DSP&country=be&language=en&preparationdelay=5&street=%C3%A9ssayer+d%27escaper+%C3%A7a+str%C3%AFng+corr%C3%A8ct%C3%A9+33&zip=2550&city=Vr%C3%A8sse-sur-m%C3%A8s%C3%BBre&sort-method=&max-result=1";
        $httpClient = $this->getMock('Zend_Http_Client', array('setUri', 'request'));
        $httpClient->expects($this->once())
                ->method('setUri')
                ->with($this->equalTo($uri));

        $httpResponse = new Zend_Http_Response(200, array(), $this->getXml());  // $data - response you expected to get
        $httpClient->expects($this->any())
                ->method('request')
                ->will($this->returnValue($httpResponse));

        $shippingAddress = $this->getShippingAddressMock();

        $model = Mage::getModel("locateandselect/LocateAndSelect");
        $model->_httpClient = $httpClient;
        $kialaPoints = $model->getKialaPoints($uri);
        $this->assertTrue(is_array($kialaPoints));
        $this->assertEquals("Kiala_LocateAndSelect_Model_KialaPoint", get_class($kialaPoints[0]));
    }

    /**
     *
     * @test
     * @loadFixture kialaConfig.yaml
     * @group getKialaPointByShortId
     * @doNotIndexAll
     */
    public function getKialaPointByShortId() {
        $uri = "http://locateandselect.kiala.com/kplist?dspid=DEMO_DSP&country=be&shortID=0210";
        $httpClient = $this->getMock('Zend_Http_Client', array('setUri', 'request'));
        $httpClient->expects($this->once())
                ->method('setUri')
                ->with($this->equalTo($uri));


        $httpResponse = new Zend_Http_Response(200, array(), $this->getXml());  // $data - response you expected to get
        $httpClient->expects($this->any())
                ->method('request')
                ->will($this->returnValue($httpResponse));

        $shippingAddress = $this->getShippingAddressMock(true);

        $model = Mage::getModel("locateandselect/LocateAndSelect");
        $model->_httpClient = $httpClient;
        $result = $model->getKialaPoints($uri);
        $this->assertEquals("Kiala_LocateAndSelect_Model_KialaPoint", get_class($result[0]));
    }

    /**
     *
     * @test
     * @loadFixture kialaConfig.yaml
     * @group locateNearestKialaPoint
     * @doNotIndexAll
     */
    public function getKialaPointReturnsNullWhenCallFails() {
        $httpResponse = $this->getMock('Zend_Http_Response', array());
        $httpResponse->expects($this->any())
                ->method('isError')
                ->will($this->returnValue(true));

        $httpClient = $this->getMock('Zend_Http_Client', array());
        $httpClient->expects($this->any())
                ->method('request')
                ->will($this->returnValue($httpResponse));

        $shippingAddress = $this->getShippingAddressMock();

        $model = Mage::getModel("locateandselect/LocateAndSelect");
        $model->_httpClient = $httpClient;
        $this->assertEquals(array(), $model->getKialaPoints($shippingAddress));
    }

    /**
     * @test
     * @loadFixture kialaConfig.yaml
     * @doNotIndexAll
     * @group updateShippingAddressWithKialaPointAddress
     */
    public function updateShippingAddressWithKialaPointAddress() {
              
        $shippingAddress = $this->getModelMock('sales/quote_address', array('setSameAsBilling', 'getStreet', 'getPostcode', 'getCity', 'getShippingMethod', 'save', 'setCompany', 'setStreet', 'setPostcode', 'setCity'));
        $shippingAddress->expects($this->any())
                ->method("getStreet")
                ->will($this->returnValue(array("éssayer d'escaper ça strïng corrècté", "33")));
        $shippingAddress->expects($this->any())
                ->method("getPostcode")
                ->will($this->returnValue("2550"));
        $shippingAddress->expects($this->any())
                ->method("getCity")
                ->will($this->returnValue("Vrèsse-sur-mèsûre"));
        $shippingAddress->expects($this->any())
                ->method('getShippingMethod')
                ->will($this->returnValue('kiala_0210'));
        $shippingAddress->expects($this->once())
                ->method('save');
        $shippingAddress->expects($this->once())
                ->method('setCompany')
                ->with($this->equalTo('UPSELL'));
        $shippingAddress->expects($this->once())
                ->method('setSameAsBilling')
                ->with($this->equalTo(false));
        
        $shippingAddress->expects($this->once())
                ->method('setStreet')
                ->with($this->equalTo('Veldkant, 39'));
        $shippingAddress->expects($this->once())
                ->method('setPostcode')
                ->with($this->equalTo('2550'));
        $shippingAddress->expects($this->once())
                ->method('setCity')
                ->with($this->equalTo('Kontich'));
        $this->replaceByMock('model', 'sales/quote_address', $shippingAddress);

        $locateAndSelect = $this->getModelMock('locateandselect/LocateAndSelect', array('getShippingAddressForCurrentCheckoutSession', 'getKialaPointsForCurrentCheckoutSession'));
        $kp = $locateAndSelect->transformXmlToKialaPoints($this->getXml());

        $locateAndSelect->expects($this->any())
                ->method("getShippingAddressForCurrentCheckoutSession")
                ->will($this->returnValue($shippingAddress));
        $locateAndSelect->expects($this->any())
                ->method("getKialaPointsForCurrentCheckoutSession")
                ->will($this->returnValue($kp));
        $this->replaceByMock('model', 'locateandselect/LocateAndSelect', $locateAndSelect);

        $locateAndSelect->updateShippingAddressWithKialaPointAddress();
    }  

    /**
     * @test
     * @loadFixture kialaConfig.yaml
     * @doNotIndexAll
     * @group updateShippingAddressWithKialaPointAddress
     */
    public function updateShippingAddressWithKialaPointAddressIsNotUpdatedWhenCurrentCarrierIsNotKiala() {
        $shippingAddress = $this->getModelMock('sales/quote_address', array('setSameAsBilling', 'getStreet', 'getPostcode', 'getCity', 'getShippingMethod', 'save', 'setCompany', 'setStreet', 'setPostcode', 'setCity'));
        $shippingAddress->expects($this->any())
                ->method("getStreet")
                ->will($this->returnValue(array("éssayer d'escaper ça strïng corrècté", "33")));
        $shippingAddress->expects($this->any())
                ->method("getPostcode")
                ->will($this->returnValue("2550"));
        $shippingAddress->expects($this->any())
                ->method("getCity")
                ->will($this->returnValue("Vrèsse-sur-mèsûre"));
        $shippingAddress->expects($this->any())
                ->method('getShippingMethod')
                ->will($this->returnValue('flatrate_flatrate'));
        $shippingAddress->expects($this->never())
                ->method('save');
        $shippingAddress->expects($this->never())
                ->method('setCompany');
        $shippingAddress->expects($this->never())
                ->method('setSameAsBilling');
        
        $shippingAddress->expects($this->never())
                ->method('setStreet');
        $shippingAddress->expects($this->never())
                ->method('setPostcode');
        $shippingAddress->expects($this->never())
                ->method('setCity');
        $this->replaceByMock('model', 'sales/quote_address', $shippingAddress);

        $locateAndSelect = $this->getModelMock('locateandselect/LocateAndSelect', array('getShippingAddressForCurrentCheckoutSession', 'getKialaPointsForCurrentCheckoutSession'));
        $kp = $locateAndSelect->transformXmlToKialaPoints($this->getXml());
        $locateAndSelect->expects($this->any())
                ->method("getShippingAddressForCurrentCheckoutSession")
                ->will($this->returnValue($shippingAddress));
        $locateAndSelect->expects($this->never())
                ->method("getKialaPointsForCurrentCheckoutSession");
        $this->replaceByMock('model', 'locateandselect/LocateAndSelect', $locateAndSelect);
        $locateAndSelect->updateShippingAddressWithKialaPointAddress();
    }    
    
    /**
     * @test
     * @loadFixture kialaConfig.yaml
     * @doNotIndexAll
     * @group updateShippingAddressWithKialaPointAddress
     */
    public function updateShippingAddressWithKialaPointAddressIsNotUpdatedWhenKialaIsNotEnabled() {
        $shippingAddress = $this->getModelMock('sales/quote_address', array('setSameAsBilling','save', 'setCompany', 'setStreet', 'setPostcode', 'setCity'));
        $shippingAddress->expects($this->any())
                ->method('getShippingMethod')
                ->will($this->returnValue('flatrate_flatrate'));
        $shippingAddress->expects($this->never())
                ->method('save');
        $shippingAddress->expects($this->never())
                ->method('setCompany');
        $shippingAddress->expects($this->never())
                ->method('setSameAsBilling');
        $shippingAddress->expects($this->never())
                ->method('setStreet');
        $shippingAddress->expects($this->never())
                ->method('setPostcode');
        $shippingAddress->expects($this->never())
                ->method('setCity');
        $this->replaceByMock('model', 'sales/quote_address', $shippingAddress);

        $helper = $this->getHelperMock('locateandselect/Data', array('IsActive', 'getProxyHost', 'getProxyPort'));
        $helper->expects($this->any())
                ->method("IsActive")
                ->will($this->returnValue(false));
        $helper->expects($this->any())
                ->method("getProxyHost")
                ->will($this->returnValue(""));
        $helper->expects($this->any())
                ->method("getProxyPort")
                ->will($this->returnValue(""));
        $this->replaceByMock('helper', 'locateandselect/data', $helper);
        
        $locateAndSelect = $this->getModelMock('locateandselect/LocateAndSelect', array('getProxyConfig', 'kialaHelper', 'getShippingAddressForCurrentCheckoutSession', 'getKialaPointsForCurrentCheckoutSession'));
        $locateAndSelect->expects($this->never())
                ->method("getShippingAddressForCurrentCheckoutSession");
        $locateAndSelect->expects($this->never())
                ->method("getKialaPointsForCurrentCheckoutSession");
        $locateAndSelect->expects($this->any())
                ->method("kialaHelper")
                ->will($this->returnValue($helper));  
        $locateAndSelect->expects($this->any())
                ->method("getProxyConfig")
                ->will($this->returnValue(array()));    
                
        $this->replaceByMock('model', 'locateandselect/LocateAndSelect', $locateAndSelect);
        $locateAndSelect->updateShippingAddressWithKialaPointAddress();
    } 
    
    /**
     *
     * @test
     * @loadFixture kialaConfig.yaml
     * @group updateKialaShippingMethodWithNewKialaPointDetails
     * @doNotIndexAll
     */    
    function updateKialaShippingMethodWithNewKialaPointDetails(){
        $rate = $this->getModelMock('sales/quote_address_rate', array("getCarrier", "setCode", "setMethod", "setMethodTitle", "save"));
        $rate->expects($this->once())
                ->method('setCode')
                ->with($this->equalTo('kiala_kiala_0210'));
        $rate->expects($this->once())
                ->method('setMethod')
                ->with($this->equalTo('kiala_0210'));
        $rate->expects($this->once())
                ->method('setMethodTitle')
                ->with($this->equalTo('UPSELL'));
        $rate->expects($this->once())
                ->method("save")
                ->will($this->returnValue(true));
        $rate->expects($this->any())
                ->method("getCarrier")
                ->will($this->returnValue("kiala"));
                                            
        $shippingAddress = $this->getModelMock('sales/quote_address', array('getShippingRatesCollection', 'save'));

        $shippingAddress->expects($this->any())
                ->method("getShippingRatesCollection")
                ->will($this->returnValue(array($rate)));
        $rate->expects($this->once())
                ->method("save")
                ->will($this->returnValue(true));

        $this->replaceByMock('model', 'sales/quote_address', $shippingAddress);

        $locateAndSelect = $this->getModelMock('locateandselect/LocateAndSelect', array('getShippingAddressForCurrentCheckoutSession', 'getKialaPointsForShortId'));
        $kp = $locateAndSelect->transformXmlToKialaPoints($this->getXml());
        $locateAndSelect->expects($this->any())
                ->method("getShippingAddressForCurrentCheckoutSession")
                ->will($this->returnValue($shippingAddress));
        $locateAndSelect->expects($this->any())
                ->method("getKialaPointsForShortId")
                ->will($this->returnValue($kp));
        $this->replaceByMock('model', 'locateandselect/LocateAndSelect', $locateAndSelect);

        $locateAndSelect->updateKialaShippingMethodWithNewKialaPointDetails("003215");       
    }
              
    /**
     *
     * @test
     * @loadFixture kialaConfig.yaml
     * @group buildNearestKialaPointqueryString
     * @doNotIndexAll
     */
    public function buildNearestKialaPointqueryStringReturnsQueryStringForCall() {
        $shippingAddress = $this->getShippingAddressMock();
        $model = Mage::getModel("locateandselect/LocateAndSelect");
        $expectedQueryString = "?dspid=DEMO_DSP&country=be&language=en&preparationdelay=5&street=%C3%A9ssayer+d%27escaper+%C3%A7a+str%C3%AFng+corr%C3%A8ct%C3%A9+33&zip=2550&city=Vr%C3%A8sse-sur-m%C3%A8s%C3%BBre&sort-method=ALL&max-result=1";
        $this->assertEquals($expectedQueryString, $model->buildNearestKialaPointqueryString($shippingAddress));
    }

    /**
     *
     * @test
     * @loadFixture kialaConfig.yaml
     * @group buildNearestKialaPointRequestUri
     * @doNotIndexAll
     */
    public function buildNearestKialaPointRequestUri() {
        $shippingAddress = $this->getShippingAddressMock();
        $model = Mage::getModel("locateandselect/LocateAndSelect");
        $expectedQueryString = "http://locateandselect.kiala.com/kplist?dspid=DEMO_DSP&country=be&language=en&preparationdelay=5&street=%C3%A9ssayer+d%27escaper+%C3%A7a+str%C3%AFng+corr%C3%A8ct%C3%A9+33&zip=2550&city=Vr%C3%A8sse-sur-m%C3%A8s%C3%BBre&sort-method=ALL&max-result=1";
        $this->assertEquals($expectedQueryString, $model->buildNearestKialaPointRequestUri($shippingAddress));
    }

    /**
     *
     * @test
     * @loadFixture kialaConfig.yaml
     * @group buildKialaMapRequestUri
     * @doNotIndexAll
     */
    public function buildKialaMapRequestUri() {
        $shippingAddress = $this->getShippingAddressMock();
        $model = Mage::getModel("locateandselect/LocateAndSelect");
        $expectedQueryString = "http://locateandselect.kiala.com/search?dspid=DEMO_DSP&country=be&language=en&preparationdelay=5&street=%C3%A9ssayer+d%27escaper+%C3%A7a+str%C3%AFng+corr%C3%A8ct%C3%A9+33&zip=2550&city=Vr%C3%A8sse-sur-m%C3%A8s%C3%BBre&bckUrl=http%3A%2F%2Fkiala.local%2Flocateandselect%2Fmap%2Fupdate%3F&target=_self&map-controls=off";
        $this->assertEquals($expectedQueryString, $model->buildKialaMapRequestUri($shippingAddress));
    }

    /**
     *
     * @test
     * @loadFixture kialaConfig.yaml
     * @group buildKialaMapRequestQueryString
     * @doNotIndexAll
     */
    public function buildKialaMapRequestQueryString() {
        $shippingAddress = $this->getShippingAddressMock();
        $model = Mage::getModel("locateandselect/LocateAndSelect");
        $expectedQueryString = "?dspid=DEMO_DSP&country=be&language=en&preparationdelay=5&street=%C3%A9ssayer+d%27escaper+%C3%A7a+str%C3%AFng+corr%C3%A8ct%C3%A9+33&zip=2550&city=Vr%C3%A8sse-sur-m%C3%A8s%C3%BBre&bckUrl=http%3A%2F%2Fkiala.local%2Flocateandselect%2Fmap%2Fupdate%3F&target=_self&map-controls=off";
        $this->assertEquals($expectedQueryString, $model->buildKialaMapRequestQueryString($shippingAddress));
    }
    
    /**
     *
     * @test
     * @loadFixture kialaConfig.yaml
     * @loadFixture cssUrlConfig.yaml
     * @group buildKialaMapRequestQueryString
     * @doNotIndexAll
     */
    public function buildKialaMapRequestQueryStringAddsCssUrlToqueryStringWhenItIsConfigured() {
        $shippingAddress = $this->getShippingAddressMock();
        $model = Mage::getModel("locateandselect/LocateAndSelect");
        $expectedQueryString = "?dspid=DEMO_DSP&country=be&language=en&preparationdelay=5&street=%C3%A9ssayer+d%27escaper+%C3%A7a+str%C3%AFng+corr%C3%A8ct%C3%A9+33&zip=2550&city=Vr%C3%A8sse-sur-m%C3%A8s%C3%BBre&bckUrl=http%3A%2F%2Fkiala.local%2Flocateandselect%2Fmap%2Fupdate%3F&target=_self&css=http%3A%2F%2Flocateandselect.kiala.com%2Fstatic%2Fstyle%2Fsearch%2Fsearch_public_theme.css&map-controls=off";
        $this->assertEquals($expectedQueryString, $model->buildKialaMapRequestQueryString($shippingAddress));
    }
    
    /**
     * @test 
     * @loadFixture kialaConfig.yaml
     * @group locateNearestKialaPointForCurrentCheckoutSession
     * @doNotIndexAll
     */
    public function getKialaPointsForCurrentCheckoutSessionReturnsNullWhenQuoteDoesNotExists() {
        $httpClient = $this->getMock('Zend_Http_Client', array('setUri', 'request'));
        $httpClient->expects($this->never())
                ->method('setUri');

        $checkoutSession = $this->getModelMock('checkout/session', array('getQuote'));
        $checkoutSession->expects($this->any())
                ->method("getQuote")
                ->will($this->returnValue(null));

        $this->replaceByMock('model', 'checkout/session', $checkoutSession);

        $model = Mage::getModel("locateandselect/LocateAndSelect");
        $model->_httpClient = $httpClient;

        $this->assertEquals(array(), $model->getKialaPointsForCurrentCheckoutSession());
    }

    /**
     * @test
     * @group getKialaPointsForCurrentCheckoutSession
     */
     public function getKialaPointsForCurrentCheckoutSessionReturnsKialapointBasedOnCurrentCheckoutAddress(){
        $model = Mage::getModel("locateandselect/LocateAndSelect");
        $kp_objects = $model->transformXmlToKialaPoints($this->getXml());
        
        $locateAndSelectMock = $this->getModelMock('locateandselect/LocateAndSelect', array('getKialaPointsForAddress', 'getKialaPointFromPreviousOrder', 'getShippingAddressForCurrentCheckoutSession', 'getShortIdFromShippingMethodName'));
        $locateAndSelectMock->expects($this->once())
        ->method("getShippingAddressForCurrentCheckoutSession")
        ->will($this->returnValue($this->getShippingAddressMock()));
        $locateAndSelectMock->expects($this->any())
        ->method("getShortIdFromShippingMethodName")
        ->will($this->returnValue(null));
        $locateAndSelectMock->expects($this->once())
        ->method("getKialaPointFromPreviousOrder")
        ->will($this->returnValue(null));
        $locateAndSelectMock->expects($this->once())
        ->method("getKialaPointsForAddress")
        ->will($this->returnValue($kp_objects));
        
        $kialaPoints = $locateAndSelectMock->getKialaPointsForCurrentCheckoutSession();
        $kialaPoint = current($kialaPoints);
        $this->assertEquals("Kiala_LocateAndSelect_Model_KialaPoint", get_class($kialaPoint));
        $this->assertEquals("Based on your address details, we suggest following Kiala point", $kialaPoint->getMethodDescription());
     }

    /**
     * @test
     * @group getKialaPointsForCurrentCheckoutSession
     */
     public function getKialaPointsForCurrentCheckoutSessionReturnsKialapointAlreadySelectedByCustomer(){
        $model = Mage::getModel("locateandselect/LocateAndSelect");
        $kp_objects = $model->transformXmlToKialaPoints($this->getXml());
        
        $helperMock = $this->getHelperMock('locateandselect/Data', array('getShortIdFromShippingMethodName'));
        $helperMock->expects($this->any())
        ->method("getShortIdFromShippingMethodName")
        ->will($this->returnValue("0210"));        

        $locateAndSelectMock = $this->getModelMock('locateandselect/LocateAndSelect', array('getProxyConfig', 'getKialaPointsForShortId', 'getKialaPointsForAddress', 'getKialaPointFromPreviousOrder', 'getShippingAddressForCurrentCheckoutSession', 'kialaHelper'));
        
        $locateAndSelectMock->expects($this->once())
        ->method("getShippingAddressForCurrentCheckoutSession")
        ->will($this->returnValue($this->getShippingAddressMock()));
        
        $locateAndSelectMock->expects($this->any())
        ->method("kialaHelper")
        ->will($this->returnValue($helperMock));
        
        $locateAndSelectMock->expects($this->any())
        ->method("getProxyConfig")
        ->will($this->returnValue(array()));

        $locateAndSelectMock->expects($this->never())
        ->method("getKialaPointFromPreviousOrder");
        
        $locateAndSelectMock->expects($this->never())
        ->method("getKialaPointsForAddress");
        
        $locateAndSelectMock->expects($this->once())
        ->method("getKialaPointsForShortId")
        ->with($this->equalTo('0210'))
        ->will($this->returnValue($kp_objects));
        
        $kialaPoints = $locateAndSelectMock->getKialaPointsForCurrentCheckoutSession();
        $kialaPoint = current($kialaPoints);
        $this->assertEquals("Kiala_LocateAndSelect_Model_KialaPoint", get_class($kialaPoint));
        $this->assertEquals("You selected this Kiala Point", $kialaPoint->getMethodDescription());
     }
     

    /**
     * @test
     * @group getKialaPointsForCurrentCheckoutSession
     */
     public function getKialaPointsForCurrentCheckoutSessionReturnsKialapointFromPreviousOrder(){
        $model = Mage::getModel("locateandselect/LocateAndSelect");
        
        $kp_objects = $model->transformXmlToKialaPoints($this->getXml());
        $kp_objects[0]->setMethodDescription("Based on your previous order, we suggest following Kiala point");
        
        $helperMock = $this->getHelperMock('locateandselect/Data', array('getShortIdFromShippingMethodName'));
        $helperMock->expects($this->any())
        ->method("getShortIdFromShippingMethodName")
        ->will($this->returnValue(null));        

        $locateAndSelectMock = $this->getModelMock('locateandselect/LocateAndSelect', array('getProxyConfig', 'getKialaPointsForShortId', 'getKialaPointsForAddress', 'getKialaPointFromPreviousOrder', 'getShippingAddressForCurrentCheckoutSession', 'kialaHelper'));
        
        $locateAndSelectMock->expects($this->once())
        ->method("getShippingAddressForCurrentCheckoutSession")
        ->will($this->returnValue($this->getShippingAddressMock()));
        
        $locateAndSelectMock->expects($this->any())
        ->method("kialaHelper")
        ->will($this->returnValue($helperMock));
        
        $locateAndSelectMock->expects($this->any())
        ->method("getProxyConfig")
        ->will($this->returnValue(array()));

        $locateAndSelectMock->expects($this->once())
        ->method("getKialaPointFromPreviousOrder")
        ->will($this->returnValue($kp_objects));
        
        $locateAndSelectMock->expects($this->never())
        ->method("getKialaPointsForAddress");
        
        $locateAndSelectMock->expects($this->never())
        ->method("getKialaPointsForShortId");
        
        $kialaPoints = $locateAndSelectMock->getKialaPointsForCurrentCheckoutSession();
        $kialaPoint = current($kialaPoints);
        $this->assertEquals("Kiala_LocateAndSelect_Model_KialaPoint", get_class($kialaPoint));
        $this->assertEquals("Based on your previous order, we suggest following Kiala point", $kialaPoint->getMethodDescription());
     }
          
    /**
     * @test 
     * @loadFixture kialaConfig.yaml
     * @group transformXmlToKialaPoints
     * @doNotIndexAll
     */
    public function transformXmlToKialaPoints() {
        $model = Mage::getModel("locateandselect/LocateAndSelect");
        $kp_objects = $model->transformXmlToKialaPoints($this->getXml());
        $this->assertEquals(1, count($kp_objects));
        $this->assertEquals('32010210', $kp_objects[0]->getId());
        $this->assertEquals('0210', $kp_objects[0]->getShortId());
        $this->assertEquals('UPSELL', $kp_objects[0]->getName());
        $this->assertEquals('Veldkant, 39', $kp_objects[0]->getStreet());
        $this->assertEquals('2550', $kp_objects[0]->getPostcode());
        $this->assertEquals('Kontich', $kp_objects[0]->getCity());
        $this->assertEquals('kiala_kiala_0210', $kp_objects[0]->getCode());
        $this->assertEquals('kiala_0210', $kp_objects[0]->getMethod());
        $this->assertEquals('1', $kp_objects[0]->isAvailable());
        $this->assertEquals('', $kp_objects[0]->getStatus());
        $this->assertEquals("Dicht bij uitrit van de E19, op het industrieterrein.", $kp_objects[0]->getLocationHint());
        $this->assertEquals("https://locateandselect.kiala.com/kpimages/be/32010210.jpg", $kp_objects[0]->getPictureUrl());
    }

    /**
     * @test 
     * @loadFixture kialaConfig.yaml
     * @group transformXmlToKialaPoints
     * @doNotIndexAll
     */
    public function transformXmlToMultipleKialaPoints() {
        $model = Mage::getModel("locateandselect/LocateAndSelect");
        $kp_objects = $model->transformXmlToKialaPoints($this->getXmlForMultipleKialaPoints());
        $this->assertEquals(2, count($kp_objects));
        $this->assertEquals("33012169", $kp_objects[0]->getId());
        $this->assertEquals("33012937", $kp_objects[1]->getId());
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

    public function getXmlForMultipleKialaPoints() {
        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<?xml-stylesheet href="/static/style/kplist/readable.css" type="text/css"?>
<kplist xmlns="http://locateandselect.kiala.com/schema/kplist"
 xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
 xsi:schemaLocation="http://locateandselect.kiala.com/schema/kplist http://locateandselect.kiala.com/schema/kplist-1.1.xsd"
 xml:lang="fr" dsp="DEMO_DSP" country="FR" zip="75006" date="2012-02-07T13:03:01+0100" delay="2">
 <kp id="33012169" shortId="12169">
  <name>LA PARENTHESE MUSICALE</name>
  <address>
   <street>92 RUE BLOMET</street>
   <zip>75015</zip>
   <city>PARIS</city>

   <locationHint>A L ANGLE DE LA MAIRIE 15EME</locationHint>
  </address>
  <status available="1" code="ACTIVE"/>
   <openingHours>
    <day name="MON"><timespan><start>14:00</start><end>19:00</end></timespan></day>
    <day name="TUE"><timespan><start>09:30</start><end>19:00</end></timespan><timespan><start>14:00</start><end>19:00</end></timespan></day>

    <day name="WED"><timespan><start>09:30</start><end>19:00</end></timespan><timespan><start>14:00</start><end>19:00</end></timespan></day>
    <day name="THU"><timespan><start>09:30</start><end>19:00</end></timespan><timespan><start>14:00</start><end>19:00</end></timespan></day>
    <day name="FRI"><timespan><start>09:30</start><end>19:00</end></timespan><timespan><start>14:00</start><end>19:00</end></timespan></day>

    <day name="SAT"><timespan><start>09:30</start><end>19:00</end></timespan><timespan><start>14:00</start><end>19:00</end></timespan></day>
    <day name="SUN"/>
   </openingHours>
   <picture href="http://locateandselect.kiala.com/kpimages/fr/33012169.jpg"/>
   <coordinate><latitude>48.8410098</latitude><longitude>2.3007789</longitude></coordinate>
   <label>

    <tag name="name">LA PARENTHESE MUSICALE</tag>
    <tag name="platform">1500</tag>
    <tag name="route">150000</tag>
    <tag name="group">175</tag>
   </label>
 </kp>
 <kp id="33012937" shortId="12937">

  <name>I LOVE BURGER</name>
  <address>
   <street>20  RUE DES BOULANGERS</street>
   <zip>75005</zip>
   <city>PARIS</city>
   <locationHint>EN FACE DE LA FACULTE DE JUSSIEU</locationHint>

  </address>
  <status available="1" code="ACTIVE"/>
   <openingHours>
    <day name="MON"><timespan><start>10:00</start><end>15:00</end></timespan><timespan><start>18:30</start><end>00:00</end></timespan></day>
    <day name="TUE"><timespan><start>10:00</start><end>15:00</end></timespan><timespan><start>18:30</start><end>00:00</end></timespan></day>

    <day name="WED"><timespan><start>10:00</start><end>15:00</end></timespan><timespan><start>18:30</start><end>00:00</end></timespan></day>
    <day name="THU"><timespan><start>10:00</start><end>15:00</end></timespan><timespan><start>18:30</start><end>00:00</end></timespan></day>
    <day name="FRI"><timespan><start>10:00</start><end>15:00</end></timespan><timespan><start>18:30</start><end>01:00</end></timespan></day>

    <day name="SAT"><timespan><start>11:30</start><end>16:00</end></timespan><timespan><start>18:30</start><end>01:00</end></timespan></day>
    <day name="SUN"><timespan><start>11:30</start><end>16:00</end></timespan><timespan><start>18:30</start><end>00:00</end></timespan></day>
   </openingHours>
   <picture href="http://locateandselect.kiala.com/kpimages/fr/33012937.jpg"/>
   <coordinate><latitude>48.8458064</latitude><longitude>2.3533743</longitude></coordinate>

   <label>
    <tag name="name">I LOVE BURGER</tag>
    <tag name="platform">1500</tag>
    <tag name="route">150000</tag>
    <tag name="group">175</tag>
   </label>
 </kp>

</kplist>
XML;

        return $xml;
    }

}
