<?php
class Aligent_GeoIP_Model_Observer {

    /**
     * Inserts a header into the response to allow Varnish to cache pages based
     * on country and store id if appropriate.
     */
    public function setVarnishResponseHeader() {
        $aHeaders = array(Aligent_GeoIP_Helper_Data::VARNISH_XGEOIP_SERVER_HEADER);

        // If Varnish supplies a store id header, vary the response based on the store id.
        if (array_key_exists('HTTP_X_STOREID', $_SERVER)) {
            $aHeaders[] = 'X-StoreId';
        }

        $oResponse = Mage::app()->getResponse();
        $oResponse->setHeader('Vary', implode(', ', $aHeaders) , true);

//        if (array_key_exists('HTTP_X_STOREID', $_SERVER)) {
//            $oResponse->setHeader('X-Request-StoreId', $_SERVER['HTTP_X_STOREID']);
//        }
//        $oResponse->setHeader('X-Request-GeoIP', $_SERVER[Aligent_GeoIP_Helper_Data::VARNISH_XGEOIP_SERVER_VARIABLE]);


    }
    
}
