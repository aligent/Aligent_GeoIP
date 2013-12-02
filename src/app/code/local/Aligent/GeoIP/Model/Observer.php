<?php
class Aligent_GeoIP_Model_Observer {
    const VARNISH_STOREID_SERVER_VARIABLE = 'HTTP_X_STOREID';
    const VARNISH_STOREID_HEADER = 'X-StoreId';

    /**
     * Inserts a header into the response to allow Varnish to cache pages based
     * on country and store id if appropriate.
     */
    public function setVarnishResponseHeader() {
        $aHeaders = array(Aligent_GeoIP_Helper_Data::VARNISH_XGEOIP_SERVER_HEADER);

        // If Varnish supplies a store id header, vary the response based on the store id.
        if (array_key_exists(self::VARNISH_STOREID_SERVER_VARIABLE, $_SERVER)) {
            $aHeaders[] = self::VARNISH_STOREID_HEADER;
        }

        $oResponse = Mage::app()->getResponse();
        $oResponse->setHeader('Vary', implode(', ', $aHeaders) , true);

        // Put the request sotre id and country into server vars.  Useful for debugging.
        //if (array_key_exists(self::VARNISH_STORE_ID_HEADER, $_SERVER)) {
        //    $oResponse->setHeader('X-Request-StoreId', $_SERVER[self::VARNISH_STORE_ID_HEADER]);
        //}
        //if (array_key_exists(Aligent_GeoIP_Helper_Data::VARNISH_XGEOIP_SERVER_VARIABLE, $_SERVER)) {
        //    $oResponse->setHeader('X-Request-GeoIP', $_SERVER[Aligent_GeoIP_Helper_Data::VARNISH_XGEOIP_SERVER_VARIABLE]);
        //}
    }
    
}
