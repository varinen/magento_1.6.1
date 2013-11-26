<?php

/**
 * @group LocateAndSelectHelper
 */
class Kiala_LocateAndSelect_Test_Helper_Data extends EcomDev_PHPUnit_Test_Case {

    private $_helper;

    public function setUp() {
        parent::setUp();
        $this->_helper = Mage::helper('locateandselect');
    }

    /**
     * Test kialaIsNotActiveWhenSenderIdEmpty
     *
     * @test
     * @loadFixture kialaConfigSenderIdEmpty.yaml
     * @group kialaActive
     * @doNotIndexAll
     */
    public function kialaIsNotActiveWhenSenderIdEmpty() {
        $this->assertFalse($this->_helper->isActive());
    }

    /**
     * Test kialaIsNotActiveWhenDspIdEmpty
     *
     * @test
     * @loadFixture kialaConfigDspIdEmpty.yaml
     * @group kialaActive
     * @doNotIndexAll
     */
    public function kialaIsNotActiveWhenDspIdEmpty() {
        $this->assertFalse($this->_helper->isActive());
    }

    /**
     * Test kialaIsNotActiveWhenPasswordEmpty
     *
     * @test
     * @loadFixture kialaConfigPasswordEmpty.yaml
     * @group kialaActive
     * @doNotIndexAll
     */
    public function kialaIsNotActiveWhenPasswordEmpty() {
        $this->assertFalse($this->_helper->isActive());
    }

    /**
     * Test kialaIsNotActiveWhenNotActive
     *
     * @test
     * @loadFixture kialaConfigNotActive.yaml
     * @doNotIndexAll
     */
    public function kialaIsNotActiveWhenNotActive() {
        $this->assertFalse($this->_helper->isActive());
    }

    /**
     * Test isDesktopAppUser
     *
     * @test
     * @loadFixture kialaConfig.yaml
     * @doNotIndexAll
     */
    public function isDesktopAppUser() {
        $this->assertTrue($this->_helper->isDesktopAppUser());
    }

    /**
     * Test getKialaSenderId
     *
     * @test
     * @loadFixture kialaConfig.yaml
     * @doNotIndexAll
     */
    public function getKialaSenderId() {
        $this->assertEquals("Kiala ID", $this->_helper->getKialaSenderId());
    }

    /**
     * Test getDspid
     *
     * @test
     * @loadFixture kialaConfig.yaml
     * @doNotIndexAll
     */
    public function getDspid() {
        $this->assertEquals("DEMO_DSP", $this->_helper->getDspid());
    }

    /**
     * Test getPassword
     *
     * @test
     * @loadFixture kialaConfig.yaml
     * @doNotIndexAll
     */
    public function getPassword() {
        $this->assertEquals("wachtwoord", $this->_helper->getPassword());
    }

    /**
     * Test showInline
     *
     * @test
     * @loadFixture kialaConfig.yaml
     * @doNotIndexAll
     */
    public function showInline() {
        $this->assertTrue($this->_helper->showInline());
    }

    /**
     * Test getPreparationDelay
     *
     * @test
     * @loadFixture kialaConfig.yaml
     * @doNotIndexAll
     */
    public function getPreparationDelay() {
        $this->assertEquals(5, $this->_helper->getPreparationDelay());
    }

    /**
     * Test  getFrontEndUrl
     *
     * @test
     * @loadFixture kialaConfig.yaml
     * @doNotIndexAll
     */
    public function getFrontEndUrl() {
        $this->assertEquals("http://locateandselect.kiala.com", $this->_helper->getFrontEndUrl());
    }

    /**
     * Test  getApiUri
     *
     * @test
     * @loadFixture kialaConfig.yaml
     * @doNotIndexAll
     */
    public function getApiUri() {
        $this->assertEquals("https://api.bpost.be/services/shm/", $this->_helper->getApiUri());
    }

    /**
     * Test  getProxyHost
     *
     * @test
     * @loadFixture kialaConfig.yaml
     * @doNotIndexAll
     */
    public function getProxyHost() {
        $this->assertEquals("proxy.iconos.be", $this->_helper->getProxyHost());
    }

    /**
     * Test  getProxyPort
     *
     * @test
     * @loadFixture kialaConfig.yaml
     * @doNotIndexAll
     */
    public function getProxyPort() {
        $this->assertEquals("8080", $this->_helper->getProxyPort());
    }

    /**
     * Test  isKialaShippingMethod
     *
     * @test
     * @doNotIndexAll
     */
    public function isKialaShippingMethod() {
        $this->assertFalse($this->_helper->isKialaShippingMethod(""));
        $this->assertFalse($this->_helper->isKialaShippingMethod(null));
        $this->assertFalse($this->_helper->isKialaShippingMethod("bpost_bpack@bpost"));
        $this->assertTrue($this->_helper->isKialaShippingMethod("kiala_kiala_20"));
    }

    /**
     * Test  parse Kiala shipping method
     *
     * @test
     * @group getShortIdFromShippingMethodName
     * @doNotIndexAll
     */
    public function getShortIdFromShippingMethodName() {
        $this->assertEquals(null, $this->_helper->getShortIdFromShippingMethodName(""));
        $this->assertEquals(null, $this->_helper->getShortIdFromShippingMethodName(null));
        $this->assertEquals(null, $this->_helper->getShortIdFromShippingMethodName("bpost_bpack@bpost"));
        $this->assertEquals("20", $this->_helper->getShortIdFromShippingMethodName("kiala_kiala_20"));
        $this->assertEquals("20", $this->_helper->getShortIdFromShippingMethodName("kiala_20"));
    }

}
