<?php
class Aligent_GeoIP_Model_Observer {
    const VARNISH_STOREID_SERVER_VARIABLE = 'HTTP_X_STOREID';
    const VARNISH_STOREID_HEADER = 'X-StoreId';

    const CONFIG_COUNTRY_GEOBLOCK = 'aligent_geoip/geoip/block_countries';
    const CONFIG_REDIRECT_CMS = 'aligent_geoip/geoip/redirect_cms';

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

        // Put the request store id and country into server vars.  Useful for debugging.
        //if (array_key_exists(self::VARNISH_STORE_ID_HEADER, $_SERVER)) {
        //    $oResponse->setHeader('X-Request-StoreId', $_SERVER[self::VARNISH_STORE_ID_HEADER]);
        //}
        if (array_key_exists(Aligent_GeoIP_Helper_Data::VARNISH_XGEOIP_SERVER_VARIABLE, $_SERVER)) {
            $oResponse->setHeader('X-Request-GeoIP', $_SERVER[Aligent_GeoIP_Helper_Data::VARNISH_XGEOIP_SERVER_VARIABLE]);
        }
    }


    /**
     * Observe predispatch and enforce the blocking of access from certain
     * countries.
     *
     * @param Varien_Event_Observer $oEvent Event data
     */
    public function enforceCountryGeoblock(Varien_Event_Observer $oEvent) {
        $vCountryCode = Mage::helper('aligent_geoip')->autodetectCountry();
        if ($vCountryCode !== false) {
            $aBlockedCountries = explode(',', Mage::getStoreConfig(self::CONFIG_COUNTRY_GEOBLOCK));
            if (in_array($vCountryCode, $aBlockedCountries)) {
                $oCmsPage = Mage::getModel('cms/page')->load(Mage::getStoreConfig(self::CONFIG_REDIRECT_CMS), 'identifier');
                $vCmsPageUrl = Mage::helper('cms/page')->getPageUrl($oCmsPage->getId());

                $oAction = $oEvent->getControllerAction();
                $oRequest = $oAction->getRequest();
                $vAction = $oRequest->getActionName();

                // If in a geoblocked country, allow access to the specific CMS page, but nothing else.
                if ($oRequest->getModuleName() != 'cms' ||
                    $oRequest->getControllerName() != 'page' ||
                    $oRequest->getActionName() != 'view' ||
                    $oRequest->getParam('page_id') != $oCmsPage->getId()) {
                    $oAction->setFlag($vAction, Mage_Core_Controller_Varien_Action::FLAG_NO_DISPATCH, true);
                    $oAction->getResponse()->setRedirect($vCmsPageUrl);
                }

            }
        }

    }
    
}
