<?php
/**
 * @description    Test GeoIP helper functions
 *
 * @category    Aligent
 * @package     Aligent_GeoIP
 * @copyright   Copyright (c) 2013 Aligent Consulting. (http://www.aligent.com.au)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *
 * @author         Luke Mills <luke@aligent.com.au>
 */
class Aligent_GeoIP_Test_Helper_Data extends EcomDev_PHPUnit_Test_Case
{
    
    private $origServerValues = array();
    
    private static $serverVars = array(
        Aligent_GeoIP_Helper_Data::VARNISH_XGEOIP_SERVER_VARIABLE,
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_X_CLUSTER_CLIENT_IP',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR',
    );
    
    protected function setUp() {
        parent::setUp();
        foreach (self::$serverVars as $serverVar) {
            if (isset($_SERVER[$serverVar])) {
                $this->origServerValues[$serverVar] = $_SERVER[$serverVar];
            } elseif (isset($this->origServerValues[$serverVar])) {
                unset($this->origServerValues[$serverVar]);
            }
        }
    }
    
    protected function tearDown() {
        parent::tearDown();
        foreach (self::$serverVars as $serverVar) {
            if (!isset($this->origServerValues[$serverVar]) && isset($_SERVER[$serverVar])) {
                unset($_SERVER[$serverVar]);
            } elseif (isset($this->origServerValues[$serverVar])) {
                $_SERVER[$serverVar] = $this->origServerValues[$serverVar];
                unset($this->origServerValues[$serverVar]);
            }
        }
    }
    
    /**
     * @dataProvider dataProvider
     */
    public function testGetCountryByIpv4Addr($ip, $expected) {
        $country = Mage::helper('aligent_geoip')->getCountryByIpv4Addr($ip);
        $this->assertSame($expected, $country);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testGetCountryFromVarnish($xGeoIp, $expected) {
        $_SERVER[Aligent_GeoIP_Helper_Data::VARNISH_XGEOIP_SERVER_VARIABLE] = $xGeoIp;
        $country = Mage::helper('aligent_geoip')->getCountryFromVarnish();
        $this->assertSame($expected, $country);
    }

    /**
     * @dataProvider dataProvider
     */
    public function testAutodetectCountry($serverVars, $expected) {
        foreach ($serverVars as $serverVar => $value) {
            $_SERVER[$serverVar] = $value;
        }
        $country = Mage::helper('aligent_geoip')->autodetectCountry();
        $this->assertSame($expected, $country);
    }
    
}
