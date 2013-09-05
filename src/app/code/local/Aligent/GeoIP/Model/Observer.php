<?php
class Aligent_GeoIP_Model_Observer
{

    /**
     * Inserts a header into the response to allow Varnish to cache pages based on country
     */
    public function setVarnishResponseHeader() {
        Mage::app()->getResponse()->setHeader('Vary', Aligent_GeoIP_Helper_Data::VARNISH_XGEOIP_SERVER_HEADER, true);
    }
    
}
